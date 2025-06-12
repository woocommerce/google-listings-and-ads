<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\ProductNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;
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
	 * @var JobRepository
	 */
	protected $job_repository;

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
		$this->job_repository        = $job_repository;
		$this->merchant_center       = $merchant_center;
		$this->notifications_service = $notifications_service;
		$this->wc                    = $wc;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		// only register the hooks if Merchant Center is connected correctly.
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			return;
		}

		// when a product is added / updated, schedule an "update" job.
		add_action( 'woocommerce_new_product', [ $this, 'update_by_id' ], 90 );
		add_action( 'woocommerce_new_product_variation', [ $this, 'update_by_id' ], 90 );
		add_action( 'woocommerce_update_product', [ $this, 'update_by_object' ], 90, 2 );
		add_action( 'woocommerce_update_product_variation', [ $this, 'update_by_object' ], 90, 2 );

		// if we don't attach to these we miss product gallery updates.
		add_action( 'woocommerce_process_product_meta', [ $this, 'update_by_id' ], 90 );

		// when a product is trashed or removed, schedule a "delete" job.
		add_action( 'wp_trash_post', [ $this, 'pre_delete' ], 90 );
		add_action( 'before_delete_post', [ $this, 'pre_delete' ], 90 );
		add_action( 'woocommerce_before_delete_product_variation', [ $this, 'pre_delete' ], 90 );
		add_action( 'trashed_post', [ $this, 'delete' ], 90 );
		add_action( 'deleted_post', [ $this, 'delete' ], 90 );

		// when a product is restored from the trash, schedule an "update" job.
		add_action( 'untrashed_post', [ $this, 'update_by_id' ], 90 );

		// exclude the sync metadata when duplicating the product
		add_filter(
			'woocommerce_duplicate_product_exclude_meta',
			[ $this, 'duplicate_product_exclude_meta' ],
			90
		);
	}

	/**
	 * Update a Product by WC_Product
	 *
	 * @param int        $product_id
	 * @param WC_Product $product
	 */
	public function update_by_object( int $product_id, WC_Product $product ) {
		$this->handle_update_products( [ $product ] );
	}

	/**
	 * Update a Product by the ID
	 *
	 * @param int $product_id
	 */
	public function update_by_id( int $product_id ) {
		$product = $this->wc->maybe_get_product( $product_id );
		$this->handle_update_products( [ $product ] );
	}

	/**
	 * Pre delete a Product by the ID
	 *
	 * @param int $product_id
	 */
	public function pre_delete( int $product_id ) {
		$this->handle_pre_delete_product( $product_id );
	}

	/**
	 * Delete a Product by the ID
	 *
	 * @param int $product_id
	 */
	public function delete( int $product_id ) {
		$this->handle_delete_product( $product_id );
	}

	/**
	 * Filters woocommerce_duplicate_product_exclude_meta adding some custom prefix
	 *
	 * @param array $exclude_meta
	 * @return array
	 */
	public function duplicate_product_exclude_meta( array $exclude_meta ): array {
		return $this->get_duplicated_product_excluded_meta( $exclude_meta );
	}

	/**
	 * Handle updating of a product.
	 *
	 * @param WC_Product[] $products The products being saved.
	 * @param bool         $notify If true. It will try to handle notifications.
	 *
	 * @return void
	 */
	protected function handle_update_products( array $products, $notify = true ) {
		$products_to_update = [];
		$products_to_delete = [];

		foreach ( $products as $product ) {

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$product_id = $product->get_id();

			// Avoid to handle variations directly. We handle them from the parent.
			if ( $this->notifications_service->is_ready( NotificationsService::DATATYPE_PRODUCT ) && $notify ) {
				$this->handle_update_product_notification( $product );
			}

			// Bail if an event is already scheduled for this product in the current request
			if ( $this->is_already_scheduled_to_update( $product_id ) ) {
				continue;
			}

			// If it's a variable product we handle each variation separately
			if ( $product instanceof WC_Product_Variable ) {
				// This is only for MC Push mechanism. We don't handle notifications here.
				$this->handle_update_products( $product->get_available_variations( 'objects' ), false );
				continue;
			}

			// Schedule an update job if product sync is enabled.
			if ( $this->product_helper->is_sync_ready( $product ) ) {
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

		if ( ! empty( $products_to_update ) ) {
			$this->job_repository->get( UpdateProducts::class )->schedule( [ $products_to_update ] );
		}

		if ( ! empty( $products_to_delete ) ) {
			$request_entries = $this->batch_helper->generate_delete_request_entries( $products_to_delete );
			$this->job_repository->get( DeleteProducts::class )->schedule( [ BatchProductIDRequestEntry::convert_to_id_map( $request_entries )->get() ] );
		}
	}

	/**
	 * Schedules notifications for an updated product
	 *
	 * @param WC_Product $product
	 */
	protected function handle_update_product_notification( WC_Product $product ) {
		if ( $this->product_helper->should_trigger_create_notification( $product ) ) {
			$this->product_helper->set_notification_status( $product, NotificationStatus::NOTIFICATION_PENDING_CREATE );
			$this->job_repository->get( ProductNotificationJob::class )->schedule(
				[
					'item_id' => $product->get_id(),
					'topic'   => NotificationsService::TOPIC_PRODUCT_CREATED,
				]
			);
		} elseif ( $this->product_helper->should_trigger_update_notification( $product ) ) {
			$this->product_helper->set_notification_status( $product, NotificationStatus::NOTIFICATION_PENDING_UPDATE );
			$this->job_repository->get( ProductNotificationJob::class )->schedule(
				[
					'item_id' => $product->get_id(),
					'topic'   => NotificationsService::TOPIC_PRODUCT_UPDATED,
				]
			);
		} elseif ( $this->product_helper->should_trigger_delete_notification( $product ) ) {
			$this->schedule_delete_notification( $product );
			// Schedule variation deletion when the parent is deleted.
			if ( $product instanceof WC_Product_Variable ) {
				foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
					$this->handle_update_product_notification( $variation );
				}
			}
		}
	}

	/**
	 * Handle deleting of a product.
	 *
	 * @param int $product_id
	 */
	protected function handle_delete_product( int $product_id ) {
		if ( isset( $this->delete_requests_map[ $product_id ] ) ) {
			$product_id_map = BatchProductIDRequestEntry::convert_to_id_map( $this->delete_requests_map[ $product_id ] )->get();
			if ( ! empty( $product_id_map ) && ! $this->is_already_scheduled_to_delete( $product_id ) ) {
				$this->job_repository->get( DeleteProducts::class )->schedule( [ $product_id_map ] );
				$this->set_already_scheduled_to_delete( $product_id );
			}
		}
	}

	/**
	 * Maybe send the product deletion notification
	 * and mark the product as un-synced after.
	 *
	 * @since 2.8.0
	 * @param int $product_id
	 */
	protected function maybe_send_delete_notification( int $product_id ) {
		$product = $this->wc->maybe_get_product( $product_id );
		if ( $product instanceof WC_Product && $this->product_helper->has_notified_creation( $product ) ) {
			$result = $this->notifications_service->notify( NotificationsService::TOPIC_PRODUCT_DELETED, $product_id, [ 'offer_id' => $this->product_helper->get_offer_id( $product_id ) ] );
			if ( $result ) {
				$this->product_helper->set_notification_status( $product, NotificationStatus::NOTIFICATION_DELETED );
				$this->product_helper->mark_as_unsynced( $product );
			}
		}
	}

	/**
	 * Schedules a job to send the product deletion notification
	 *
	 * @since 2.8.0
	 * @param WC_Product $product
	 */
	protected function schedule_delete_notification( $product ) {
		$this->product_helper->set_notification_status( $product, NotificationStatus::NOTIFICATION_PENDING_DELETE );
		$this->job_repository->get( ProductNotificationJob::class )->schedule(
			[
				'item_id' => $product->get_id(),
				'topic'   => NotificationsService::TOPIC_PRODUCT_DELETED,
			]
		);
	}

	/**
	 * Create request entries for the product (containing its Google ID) so that we can schedule a delete job when the
	 * product is actually trashed / deleted.
	 *
	 * @param int $product_id
	 */
	protected function handle_pre_delete_product( int $product_id ) {
		if ( $this->notifications_service->is_ready( NotificationsService::DATATYPE_PRODUCT ) ) {
			/**
			 * For deletions, we do send directly the notification instead of scheduling it.
			 * This is because we want to avoid that the product is not in the database anymore when the scheduled action runs.
			 */
			$this->maybe_send_delete_notification( $product_id );
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
}
