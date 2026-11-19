<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateSmallImageProductCount
 *
 * Recalculates the number of synced products with a small main image outside the request
 * that asked for it, so the catalog scan never runs during an admin page load.
 *
 * @since 3.9.6
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateSmallImageProductCount extends AbstractActionSchedulerJob {

	/**
	 * @var ProductImageSizeAudit
	 */
	protected $image_size_audit;

	/**
	 * UpdateSmallImageProductCount constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductImageSizeAudit     $image_size_audit
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, ProductImageSizeAudit $image_size_audit ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->image_size_audit = $image_size_audit;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_small_image_product_count';
	}

	/**
	 * Schedule the job, unless a recalculation is already pending or running.
	 *
	 * @param array $args Unused.
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule() ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook() );
		}
	}

	/**
	 * Recalculate and cache the count.
	 *
	 * @param array $items Unused.
	 */
	protected function process_items( array $items ) {
		$this->image_size_audit->refresh_small_image_product_count();
	}
}
