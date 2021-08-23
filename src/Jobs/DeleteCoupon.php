<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteCoupon
 *
 * Delete existing WooCommerce coupon from Google Merchant Center.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class DeleteCoupon extends AbstractCouponSyncerJob implements StartOnHookInterface {

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
	 * @param  int $coupon_id
	 *
	 * @throws CouponSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 * @throws JobException If invalid or non-existing coupon are provided. The exception will be logged by ActionScheduler.
	 */
	public function process_items( $coupon_id ) {
	    $coupon = $this->$wc->may_get_coupon( $coupon_id );
	    if ( $coupon instanceof WC_Coupon && $this->coupon_helper->is_sync_ready( $coupon ) ) {
	       return;
	    }
	    // TODO: Pass in google id to support delete coupon.
	    $this->$coupon_syncer->delete( $coupon_id );
	}

	/**
	 * Schedule the job.
	 *
	 * @param array[] $args
	 * 
	 * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function schedule(array $args = []) {
	    $coupon_id = $args[0] ?? null;

	    if ( !is_int( $coupon_id ) ) {
	        throw JobException::item_not_provided( 'WooCommerce Coupon IDs' );
	    }
	    
	    if ( $this->can_schedule( [ $coupon_id ] ) ) {
	        $this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $coupon_id ] );
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
