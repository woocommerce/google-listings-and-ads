<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteProducts
 *
 * Deletes WooCommerce products from Google Merchant Center.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class DeleteProducts extends AbstractActionSchedulerJob {

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
		return 'delete_products';
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
	 * @param string[] $product_id_map An array of Google product IDs mapped to WooCommerce product IDs as their key.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	public function process_items( array $product_id_map ) {
		$product_entries = $this->generate_delete_requests( $product_id_map );
		$this->product_syncer->delete_by_batch_requests( $product_entries );
	}

	/**
	 * Start the job.
	 *
	 * @param array $args
	 */
	public function start( $args = [] ) {
		if ( ! empty( $args ) && $this->can_start( $args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $args ] );
		}
	}

	/**
	 * @param string[] $product_id_map An array of Google product IDs mapped to WooCommerce product IDs as their key.
	 *
	 * @return BatchProductRequestEntry[]
	 */
	protected function generate_delete_requests( array $product_id_map ): array {
		$product_entries = [];
		foreach ( $product_id_map as $wc_product_id => $google_product_id ) {
			$product_entries[] = new BatchProductRequestEntry( $wc_product_id, $google_product_id );
		}

		return $product_entries;
	}
}
