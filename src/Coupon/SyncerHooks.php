<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\DeleteCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Coupon;
defined( 'ABSPATH' ) || exit();

/**
 * Class SyncerHooks
 *
 * Hooks to various WooCommerce and WordPress actions to provide automatic coupon sync functionality.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class SyncerHooks implements Service, Registerable {

	use PluginHelper;

	protected const SCHEDULE_TYPE_UPDATE = 'update';

	protected const SCHEDULE_TYPE_DELETE = 'delete';

	/**
	 * Array of strings mapped to coupon IDs indicating that they have been already
	 * scheduled for update or delete during current request.
	 * Used to avoid scheduling
	 * duplicate jobs.
	 *
	 * @var string[]
	 */
	protected $already_scheduled = [];

	/**
	 *
	 * @var DeleteCouponEntry[][]
	 */
	protected $delete_requests_map;

	/**
	 *
	 * @var CouponHelper
	 */
	protected $coupon_helper;

	/**
	 *
	 * @var UpdateCoupon
	 */
	protected $update_coupon_job;

	/**
	 *
	 * @var DeleteCoupon
	 */
	protected $delete_coupon_job;

	/**
	 *
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 *
	 * @var WC
	 */
	protected $wc;

	/**
	 * SyncerHooks constructor.
	 *
	 * @param CouponHelper          $coupon_helper
	 * @param JobRepository         $job_repository
	 * @param MerchantCenterService $merchant_center
	 * @param WC                    $wc
	 */
	public function __construct(
		CouponHelper $coupon_helper,
		JobRepository $job_repository,
		MerchantCenterService $merchant_center,
		WC $wc ) {
		$this->update_coupon_job = $job_repository->get( UpdateCoupon::class );
		$this->delete_coupon_job = $job_repository->get( DeleteCoupon::class );
		$this->coupon_helper     = $coupon_helper;
		$this->merchant_center   = $merchant_center;
		$this->wc                = $wc;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		// only register the hooks if Merchant Center is set up and ready for syncing data.
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			return;
		}

		$update_by_id = function ( int $coupon_id ) {
			$coupon = $this->wc->maybe_get_coupon( $coupon_id );
			if ( $coupon instanceof WC_Coupon ) {
				$this->handle_update_coupon( $coupon );
			}
		};

		$pre_delete = function ( int $coupon_id ) {
			$this->handle_pre_delete_coupon( $coupon_id );
		};

		$delete_by_id = function ( int $coupon_id ) {
			$this->handle_delete_coupon( $coupon_id );
		};

		// when a coupon is added / updated, schedule a update job.
		add_action( 'woocommerce_new_coupon', $update_by_id, 90, 2 );
		add_action( 'woocommerce_update_coupon', $update_by_id, 90, 2 );
		add_action( 'woocommerce_gla_bulk_update_coupon', $update_by_id, 90 );

		// when a coupon is trashed or removed, schedule a delete job.
		add_action( 'wp_trash_post', $pre_delete, 90 );
		add_action( 'before_delete_post', $pre_delete, 90 );
		add_action(
			'woocommerce_before_delete_product_variation',
			$pre_delete,
			90
		);
		add_action( 'trashed_post', $delete_by_id, 90 );
		add_action( 'deleted_post', $delete_by_id, 90 );
		add_action( 'woocommerce_delete_coupon', $delete_by_id, 90, 2 );
		add_action( 'woocommerce_trash_coupon', $delete_by_id, 90, 2 );

		// when a coupon is restored from trash, schedule a update job.
		add_action( 'untrashed_post', $update_by_id, 90 );
	}

	/**
	 * Handle updating of a coupon.
	 *
	 * @param WC_Coupon $coupon
	 *            The coupon being saved.
	 *
	 * @return void
	 */
	protected function handle_update_coupon( WC_Coupon $coupon ) {
		$coupon_id = $coupon->get_id();

		// Schedule an update job if product sync is enabled.
		if ( $this->coupon_helper->is_sync_ready( $coupon ) ) {
			$this->coupon_helper->mark_as_pending( $coupon );
			$this->update_coupon_job->schedule(
				[
					[ $coupon_id ],
				]
			);
		} elseif ( $this->coupon_helper->is_coupon_synced( $coupon ) ) {
			// Delete the coupon from Google Merchant Center if it's already synced BUT it is not sync ready after the edit.
			$coupon_to_delete = new DeleteCouponEntry(
				$coupon_id,
				$this->get_coupon_to_delete( $coupon ),
				$this->coupon_helper->get_synced_google_ids( $coupon )
			);
			$this->delete_coupon_job->schedule(
				[
					$coupon_to_delete,
				]
			);

			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Deleting coupon (ID: %s) from Google Merchant Center because it is not ready to be synced.',
					$coupon->get_id()
				),
				__METHOD__
			);
		} else {
			$this->coupon_helper->mark_as_unsynced( $coupon );
		}
	}

	/**
	 * Create request entries for the coupon (containing its Google ID),
	 * so we can schedule a delete job when it is actually trashed / deleted.
	 *
	 * @param int $coupon_id
	 */
	protected function handle_pre_delete_coupon( int $coupon_id ) {
		$coupon = $this->wc->maybe_get_coupon( $coupon_id );

		if ( $coupon instanceof WC_Coupon &&
			$this->coupon_helper->is_coupon_synced( $coupon ) ) {
			$this->delete_requests_map[ $coupon_id ] = new DeleteCouponEntry(
				$coupon_id,
				$this->get_coupon_to_delete( $coupon ),
				$this->coupon_helper->get_synced_google_ids( $coupon )
			);
		}
	}

	/**
	 * @param WC_Coupon $coupon
	 *
	 * @return WCCouponAdapter
	 */
	protected function get_coupon_to_delete( WC_Coupon $coupon ): WCCouponAdapter {
		$adapted_coupon_to_delete = new WCCouponAdapter(
			[
				'wc_coupon' => $coupon,
			]
		);

		// Promotion stored in Google can only be soft-deleted to keep historical records.
		// Instead of 'delete', we update the promotion with effective dates expired.
		// Here we reset an expiring date based on WooCommerce coupon source.
		$adapted_coupon_to_delete->disable_promotion( $coupon );

		return $adapted_coupon_to_delete;
	}

	/**
	 * Handle deleting of a coupon.
	 *
	 * @param int $coupon_id
	 */
	protected function handle_delete_coupon( int $coupon_id ) {
		if ( ! isset( $this->delete_requests_map[ $coupon_id ] ) ) {
			return;
		}

		$coupon_to_delete = $this->delete_requests_map[ $coupon_id ];
		if ( ! empty( $coupon_to_delete->get_synced_google_ids() ) &&
				! $this->is_already_scheduled_to_delete( $coupon_id ) ) {
			$this->delete_coupon_job->schedule(
				[
					$coupon_to_delete,
				]
			);
			$this->set_already_scheduled_to_delete( $coupon_id );
		}
	}

	/**
	 *
	 * @param int    $coupon_id
	 * @param string $schedule_type
	 *
	 * @return bool
	 */
	protected function is_already_scheduled(
		int $coupon_id,
		string $schedule_type ): bool {
		return isset( $this->already_scheduled[ $coupon_id ] ) &&
			$this->already_scheduled[ $coupon_id ] === $schedule_type;
	}

	/**
	 *
	 * @param int $coupon_id
	 *
	 * @return bool
	 */
	protected function is_already_scheduled_to_update( int $coupon_id ): bool {
		return $this->is_already_scheduled(
			$coupon_id,
			self::SCHEDULE_TYPE_UPDATE
		);
	}

	/**
	 *
	 * @param int $coupon_id
	 *
	 * @return bool
	 */
	protected function is_already_scheduled_to_delete( int $coupon_id ): bool {
		return $this->is_already_scheduled(
			$coupon_id,
			self::SCHEDULE_TYPE_DELETE
		);
	}

	/**
	 *
	 * @param int    $coupon_id
	 * @param string $schedule_type
	 *
	 * @return void
	 */
	protected function set_already_scheduled(
		int $coupon_id,
		string $schedule_type ): void {
		$this->already_scheduled[ $coupon_id ] = $schedule_type;
	}

	/**
	 *
	 * @param int $coupon_id
	 *
	 * @return void
	 */
	protected function set_already_scheduled_to_update( int $coupon_id ): void {
		$this->set_already_scheduled( $coupon_id, self::SCHEDULE_TYPE_UPDATE );
	}

	/**
	 *
	 * @param int $coupon_id
	 *
	 * @return void
	 */
	protected function set_already_scheduled_to_delete( int $coupon_id ): void {
		$this->set_already_scheduled( $coupon_id, self::SCHEDULE_TYPE_DELETE );
	}
}

