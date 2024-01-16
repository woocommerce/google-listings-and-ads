<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notification;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Product;
use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncerHooks
 *
 * Hooks to various WooCommerce and WordPress actions to provide automatic product sync functionality.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class SyncerHooks implements Service, Registerable {

	use PluginHelper;

	protected const SCHEDULE_TYPE_CREATE = 'create';
	protected const SCHEDULE_TYPE_UPDATE = 'update';
	protected const SCHEDULE_TYPE_DELETE = 'delete';

	/**
	 * Array of strings mapped to product IDs indicating that they have been already
	 * scheduled for update or delete during current request. Used to avoid scheduling
	 * duplicate jobs.
	 *
	 * @var string[]
	 */
	protected $already_scheduled = [];

	/**
	 * @var BatchProductIDRequestEntry[][]
	 */
	protected $delete_requests_map;

	/**
	 * @var BatchProductHelper
	 */
	protected $batch_helper;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var UpdateProducts
	 */
	protected $update_products_job;

	/**
	 * @var DeleteProducts
	 */
	protected $delete_products_job;

	/**
	 * @var Notification
	 */
	protected $notification_job;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var NotificationsService
	 */
	protected $notifications_service;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * SyncerHooks constructor.
	 *
	 * @param BatchProductHelper    $batch_helper
	 * @param ProductHelper         $product_helper
	 * @param JobRepository         $job_repository
	 * @param MerchantCenterService $merchant_center
	 * @param NotificationsService  $notifications_service
	 * @param WC                    $wc
	 */
	public function __construct(
		BatchProductHelper $batch_helper,
		ProductHelper $product_helper,
		JobRepository $job_repository,
		MerchantCenterService $merchant_center,
		NotificationsService $notifications_service,
		WC $wc
	) {
		$this->batch_helper          = $batch_helper;
		$this->product_helper        = $product_helper;
		$this->update_products_job   = $job_repository->get( UpdateProducts::class );
		$this->delete_products_job   = $job_repository->get( DeleteProducts::class );
		$this->notification_job      = $job_repository->get( Notification::class );
		$this->merchant_center       = $merchant_center;
		$this->notifications_service = $notifications_service;
		$this->wc                    = $wc;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		// only register the hooks if Merchant Center is connected and ready for syncing data.
		// TODO: Potentially change this after API Pull is implemented as we don't need MC to be connected for the API Pull
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			return;
		}

		$update_by_object = function ( int $product_id, WC_Product $product ) {
			$this->handle_update_products( [ $product ] );
		};

		$update_by_id = function ( int $product_id ) {
			$product = $this->wc->maybe_get_product( $product_id );
			if ( $product instanceof WC_Product ) {
				$this->handle_update_products( [ $product ] );
			}
		};

		$pre_delete = function ( int $product_id ) {
			$this->handle_pre_delete_product( $product_id );
		};

		$delete = function ( int $product_id ) {
			$this->handle_delete_product( $product_id );
		};

		// when a product is added / updated, schedule an "update" job.
		add_action( 'woocommerce_new_product', $update_by_id, 90 );
		add_action( 'woocommerce_new_product_variation', $update_by_id, 90 );
		add_action( 'woocommerce_update_product', $update_by_object, 90, 2 );
		add_action( 'woocommerce_update_product_variation', $update_by_object, 90, 2 );

		// if we don't attach to these we miss product gallery updates.
		add_action( 'woocommerce_process_product_meta', $update_by_id, 90 );

		// when a product is trashed or removed, schedule a "delete" job.
		add_action( 'wp_trash_post', $pre_delete, 90 );
		add_action( 'before_delete_post', $pre_delete, 90 );
		add_action( 'woocommerce_before_delete_product_variation', $pre_delete, 90 );
		add_action( 'trashed_post', $delete, 90 );
		add_action( 'deleted_post', $delete, 90 );

		// when a product is restored from the trash, schedule an "update" job.
		add_action( 'untrashed_post', $update_by_id, 90 );

		// exclude the sync metadata when duplicating the product
		add_action(
			'woocommerce_duplicate_product_exclude_meta',
			function ( array $exclude_meta ) {
				return $this->get_duplicated_product_excluded_meta( $exclude_meta );
			},
			90
		);
	}

	/**
	 * Handle updating of a product.
	 *
	 * @param WC_Product[] $products The products being saved.
	 *
	 * @return void
	 */
	protected function handle_update_products( array $products ) {
		$products_to_update = [];
		$products_to_create = [];
		$products_to_delete = [];

		foreach ( $products as $product ) {
			$product_id = $product->get_id();

			// Bail if an event is already scheduled for this product in the current request
			if ( $this->is_already_scheduled_to_update( $product_id ) || $this->is_already_scheduled_to_create( $product_id ) ) {
				continue;
			}

			// If it's a variable product we handle each variation separately
			if ( $product instanceof WC_Product_Variable ) {
				$this->handle_update_products( $product->get_available_variations( 'objects' ) );
				continue;
			}

			if ( $this->product_helper->should_notify_creation( $product ) ) {
				// Notify about the product creation
				$products_to_create[] = $product->get_id();
				$this->product_helper->mark_as_notification_created( $product );
				$this->set_already_scheduled_to_create( $product_id );
			} elseif ( $this->product_helper->is_sync_ready( $product ) ) {
				// Schedule an update job if product is enabled to sync or notify.
				$this->product_helper->mark_as_pending( $product );
				$products_to_update[] = $product->get_id();
				$this->set_already_scheduled_to_update( $product_id );
			} elseif ( $this->product_helper->is_product_synced( $product ) ) {
				// Delete the product from Google Merchant Center if it's already synced BUT it is not sync ready after the edit.
				$products_to_delete[] = $product;
				$this->set_already_scheduled_to_delete( $product_id );

				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Deleting product (ID: %s) from Google Merchant Center because it is not ready to be synced.', $product->get_id() ),
					__METHOD__
				);
			} else {
				$this->product_helper->mark_as_unsynced( $product );
			}
		}

		$this->schedule_create( $products_to_create );
		$this->schedule_update( $products_to_update );
		$this->schedule_delete( $products_to_delete );
	}

	/**
	 * Prepare the items to process and Schedule the update Job.
	 * When using Notifications, we pass an array of WC_Product products. Otherwise, we send an array map containing
	 * the Google product IDs as key and the WooCommerce product IDs as values.
	 *
	 * @param int[] $products_to_update Product IDs to update
	 */
	protected function schedule_update( array $products_to_update ) {
		if ( empty( $products_to_update ) ) {
			return;
		}

		if ( $this->notifications_service->is_enabled() ) {
			$this->notification_job->schedule_notifications( $products_to_update, NotificationsService::TOPIC_PRODUCT_UPDATED );
		} else {
			$this->update_products_job->schedule( [ $products_to_update ] );
		}
	}

	/**
	 * Schedules a product deletion request
	 *
	 * @param array $products_to_delete
	 */
	protected function schedule_delete( array $products_to_delete ) {
		if ( empty( $products_to_delete ) ) {
			return;
		}

		if ( $this->notifications_service->is_enabled() ) {
			$this->notification_job->schedule_notifications( $products_to_delete, NotificationsService::TOPIC_PRODUCT_DELETED );
		} else {
			$request_entries = $this->batch_helper->generate_delete_request_entries( $products_to_delete );
			$items           = BatchProductIDRequestEntry::convert_to_id_map( $request_entries )->get();
			$this->delete_products_job->schedule( [ $items ] );
		}
	}

	/**
	 * Schedules a product creation request
	 *
	 * @param array $products_to_create
	 */
	protected function schedule_create( array $products_to_create ) {
		if ( empty( $products_to_create ) ) {
			return;
		}

		$this->notification_job->schedule_notifications( $products_to_create, NotificationsService::TOPIC_PRODUCT_CREATED );
	}


	/**
	 * Handle deleting of a product.
	 *
	 * @param int $product_id
	 */
	protected function handle_delete_product( int $product_id ) {
		if ( $this->notifications_service->is_enabled() ) {
			$product = wc_get_product( $product_id );
			if ( $product instanceof WC_Product && $this->product_helper->should_notify( $product ) ) {
				$this->set_already_scheduled_to_delete( $product_id );
				$this->notification_job->schedule_notifications( [ $product_id ], $this->notifications_service::TOPIC_PRODUCT_DELETED );
			}
			return;
		}

		if ( isset( $this->delete_requests_map[ $product_id ] ) ) {
			$product_id_map = BatchProductIDRequestEntry::convert_to_id_map( $this->delete_requests_map[ $product_id ] )->get();
			if ( ! empty( $product_id_map ) && ! $this->is_already_scheduled_to_delete( $product_id ) ) {
				$this->delete_products_job->schedule( [ $product_id_map ] );
				$this->set_already_scheduled_to_delete( $product_id );
			}
		}
	}

	/**
	 * Create request entries for the product (containing its Google ID) so that we can schedule a delete job when the
	 * product is actually trashed / deleted.
	 *
	 * @param int $product_id
	 */
	protected function handle_pre_delete_product( int $product_id ) {
		if ( $this->notifications_service->is_enabled() ) {
			return;
		}

		$product = $this->wc->maybe_get_product( $product_id );

		// each variation is passed to this method separately so we don't need to delete the variable product
		if ( $product instanceof WC_Product && ! $product instanceof WC_Product_Variable && $this->product_helper->is_product_synced( $product ) ) {
			$this->delete_requests_map[ $product_id ] = $this->batch_helper->generate_delete_request_entries( [ $product ] );
		}
	}

	/**
	 * Return the list of metadata keys to be excluded when duplicating a product.
	 *
	 * @param array $exclude_meta The keys to exclude from the duplicate.
	 *
	 * @return array
	 */
	protected function get_duplicated_product_excluded_meta( array $exclude_meta ): array {
		$exclude_meta[] = $this->prefix_meta_key( ProductMetaHandler::KEY_SYNCED_AT );
		$exclude_meta[] = $this->prefix_meta_key( ProductMetaHandler::KEY_GOOGLE_IDS );
		$exclude_meta[] = $this->prefix_meta_key( ProductMetaHandler::KEY_ERRORS );
		$exclude_meta[] = $this->prefix_meta_key( ProductMetaHandler::KEY_FAILED_SYNC_ATTEMPTS );
		$exclude_meta[] = $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_FAILED_AT );
		$exclude_meta[] = $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS );
		$exclude_meta[] = $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS );

		return $exclude_meta;
	}

	/**
	 * @param int    $product_id
	 * @param string $schedule_type
	 *
	 * @return bool
	 */
	protected function is_already_scheduled( int $product_id, string $schedule_type ): bool {
		return isset( $this->already_scheduled[ $product_id ] ) && $this->already_scheduled[ $product_id ] === $schedule_type;
	}

	/**
	 * @param int $product_id
	 *
	 * @return bool
	 */
	protected function is_already_scheduled_to_update( int $product_id ): bool {
		return $this->is_already_scheduled( $product_id, self::SCHEDULE_TYPE_UPDATE );
	}

	/**
	 * @param int $product_id
	 *
	 * @return bool
	 */
	protected function is_already_scheduled_to_delete( int $product_id ): bool {
		return $this->is_already_scheduled( $product_id, self::SCHEDULE_TYPE_DELETE );
	}

	/**
	 * @param int $product_id
	 *
	 * @return bool
	 */
	protected function is_already_scheduled_to_create( int $product_id ): bool {
		return $this->is_already_scheduled( $product_id, self::SCHEDULE_TYPE_CREATE );
	}

	/**
	 * @param int    $product_id
	 * @param string $schedule_type
	 *
	 * @return void
	 */
	protected function set_already_scheduled( int $product_id, string $schedule_type ): void {
		$this->already_scheduled[ $product_id ] = $schedule_type;
	}

	/**
	 * @param int $product_id
	 *
	 * @return void
	 */
	protected function set_already_scheduled_to_update( int $product_id ): void {
		$this->set_already_scheduled( $product_id, self::SCHEDULE_TYPE_UPDATE );
	}

	/**
	 * @param int $product_id
	 *
	 * @return void
	 */
	protected function set_already_scheduled_to_delete( int $product_id ): void {
		$this->set_already_scheduled( $product_id, self::SCHEDULE_TYPE_DELETE );
	}

	/**
	 * @param int $product_id
	 *
	 * @return void
	 */
	protected function set_already_scheduled_to_create( int $product_id ): void {
		$this->set_already_scheduled( $product_id, self::SCHEDULE_TYPE_CREATE );
	}
}
