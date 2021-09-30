<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncerException;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\DeleteCouponEntry;

defined( 'ABSPATH' ) || exit();

/**
 * Class DeleteCoupon
 *
 * Delete existing WooCommerce coupon from Google Merchant Center.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class DeleteCoupon extends AbstractCouponSyncerJob implements
	StartOnHookInterface {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'delete_coupon';
	}

	/**
	 * Process an item.
	 *
	 * @param DeleteCouponEntry[] $coupons
	 *
	 * @throws CouponSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	public function process_items( $coupons ) {
		foreach ( $coupons as $coupon ) {
			$this->coupon_syncer->delete( $coupon );
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
		$args = $args[0] ?? null;

		$coupons = [];
		foreach ( $args as $arg ) {
			if ( $arg instanceof DeleteCouponEntry ) {
				array_push( $coupons, $arg );
			}
		}

		if ( empty( $coupons ) ) {
			throw JobException::item_not_provided(
				'DeleteCouponEntrys for the coupons to delete'
			);
		}

		if ( $this->can_schedule( [ $coupons ] ) ) {
			$this->action_scheduler->schedule_immediate(
				$this->get_process_item_hook(),
				[ $coupons ]
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
