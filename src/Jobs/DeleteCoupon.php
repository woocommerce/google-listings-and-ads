<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

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
     * @param DeleteCouponEntry[] $coupon
     *
     * @throws CouponSyncerException If an error occurs. The exception will be logged by ActionScheduler.
     * @throws JobException If invalid or non-existing coupon are provided. The exception will be logged by ActionScheduler.
     */
    public function process_items( array $coupons ) {
        foreach ( $coupons as $coupon ) {
            $this->$coupon_syncer->delete( $coupon );
        }
    }

    /**
     * Schedule the job.
     *
     * @param array[] $args
     *
     * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
     */
    public function schedule( array $args = [] ) {
        $coupon = $args[0] ?? null;

        if ( ! $coupon instanceof DeleteCouponEntry ) {
            throw JobException::item_not_provided( 
                'DeleteCouponEntry for the coupon to delete' );
        }

        if ( $this->can_schedule( [$coupon] ) ) {
            $this->action_scheduler->schedule_immediate( 
                $this->get_process_item_hook(),
                [$coupon] );
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
