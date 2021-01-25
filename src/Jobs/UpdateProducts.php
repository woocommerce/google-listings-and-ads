<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateProducts
 *
 * Submits WooCommerce products to Google Merchant Center and/or updates the existing ones.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateProducts extends AbstractActionSchedulerJob {

	/**
	 * @var ProductSyncer
	 */
	protected $product_syncer;

	/**
	 * SyncProducts constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductSyncer             $product_syncer
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, ProductSyncer $product_syncer ) {
		$this->product_syncer = $product_syncer;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_products';
	}

	/**
	 * Init the job.
	 *
	 * The job name is used to generate the schedule event name.
	 */
	public function init(): void {
		add_action( $this->get_process_item_hook(), [ $this, 'process_items' ], 10, 1 );
	}

	/**
	 * Process an item.
	 *
	 * @param int[] $product_ids An array of WooCommerce product ids.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 * @throws JobException If invalid or non-existing products are provided. The exception will be logged by ActionScheduler.
	 */
	public function process_items( array $product_ids ) {
		$products = wc_get_products(
			[
				'include' => $product_ids,
				'return'  => 'objects',
			]
		);

		if ( empty( $products ) ) {
			throw JobException::item_not_found();
		}

		$this->product_syncer->update( $products );
	}

	/**
	 * Start the job.
	 *
	 * @param int[] $args An array of WooCommerce product ids.
	 *
	 * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function start( array $args = [] ) {
		if ( empty( $args ) ) {
			throw JobException::item_not_provided( 'Array of WooCommerce Product IDs' );
		}

		if ( $this->can_start( $args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $args ] );
		}
	}
}
