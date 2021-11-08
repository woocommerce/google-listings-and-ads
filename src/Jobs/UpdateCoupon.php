<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncerException;
use WC_Coupon;

defined( 'ABSPATH' ) || exit();

/**
 * Class UpdateCoupon
 *
 * Submits WooCommerce coupon to Google Merchant Center and/or updates the existing one.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateCoupon extends AbstractCouponSyncerJob implements
	StartOnHookInterface {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_coupon';
	}

	/**
	 * Process an item.
	 *
	 * @param int[] $coupon_ids
	 *
	 * @throws CouponSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	public function process_items( $coupon_ids ) {
		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = $this->wc->maybe_get_coupon( $coupon_id );
			if ( $coupon instanceof WC_Coupon &&
				$this->coupon_helper->is_sync_ready( $coupon ) ) {
				$this->coupon_syncer->update( $coupon );
			}
		}
	}

	/**
	 * Schedule the job.
	 *
	 * @param array[] $args
	 *
	 * @throws JobException If no coupon is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function schedule( array $args = [] ) {
		$args       = $args[0] ?? null;
		$coupon_ids = array_filter( $args, 'is_integer' );

		if ( empty( $coupon_ids ) ) {
			throw JobException::item_not_provided( 'WooCommerce Coupon IDs' );
		}

		if ( $this->can_schedule( [ $coupon_ids ] ) ) {
			$this->action_scheduler->schedule_immediate(
				$this->get_process_item_hook(),
				[ $coupon_ids ]
			);
		}
	}

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( "{$this->get_hook_base_name()}start" );
	}
}
