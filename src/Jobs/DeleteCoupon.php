<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncerException;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\DeleteCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Promotion as GooglePromotion;

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
	 * @param array[] $coupon_entries
	 *
	 * @throws JobException If no valid coupon data is provided as argument. The exception will be logged by ActionScheduler.
	 * @throws CouponSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	public function process_items( array $coupon_entries ) {
		$wc_coupon_id     = $coupon_entries[0] ?? null;
		$google_promotion = $coupon_entries[1] ?? null;
		$google_ids       = $coupon_entries[2] ?? null;
		if ( ( ! is_int( $wc_coupon_id ) ) || empty( $google_promotion ) || empty( $google_ids ) ) {
			throw JobException::item_not_provided(
				'Required data for the coupon to delete'
			);
		}

		$this->coupon_syncer->delete(
			new DeleteCouponEntry(
				$wc_coupon_id,
				new GooglePromotion( $google_promotion ),
				$google_ids
			)
		);
	}

	/**
	 * Schedule the job.
	 *
	 * @param array[] $args
	 *
	 * @throws JobException If no coupon is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function schedule( array $args = [] ) {
		$coupon_entry = $args[0] ?? null;

		if ( ! $coupon_entry instanceof DeleteCouponEntry ) {
			throw JobException::item_not_provided(
				'DeleteCouponEntry for the coupon to delete'
			);
		}

		if ( $this->can_schedule( [ $coupon_entry ] ) ) {
			$this->action_scheduler->schedule_immediate(
				$this->get_process_item_hook(),
				[
					[
						$coupon_entry->get_wc_coupon_id(),
						$coupon_entry->get_google_promotion(),
						$coupon_entry->get_synced_google_ids(),
					],
				]
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
