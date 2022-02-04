<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class CleanupNotFoundProducts
 *
 * Removes the Google IDs from synced products that are no longer found in the Merchant Center.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class CleanupNotFoundProducts extends AbstractBatchedActionSchedulerJob implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * CleanupNotFoundProducts constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductHelper             $product_helper
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ProductHelper $product_helper
	) {
		$this->product_helper = $product_helper;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'cleanup_not_found_products';
	}

	/**
	 * Store the items and schedule them to be processed.
	 *
	 * @param array $args
	 *
	 * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function schedule( array $args = [] ) {
		$items = $args[0] ?? [];

		if ( empty( $items ) ) {
			throw JobException::item_not_provided( 'Array of Google IDs' );
		}

		$this->options->update( OptionsInterface::GOOGLE_IDS_TO_CLEANUP, $items, false );
		parent::schedule();
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return array
	 */
	public function get_batch( int $batch_number ): array {
		$items = $this->options->get( OptionsInterface::GOOGLE_IDS_TO_CLEANUP, [] );
		return array_slice( $items, $this->get_query_offset( $batch_number ), $this->get_batch_size() );
	}

	/**
	 * Process batch items.
	 *
	 * @param string[] $items A single batch of Google IDs from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		array_walk( $items, [ $this->product_helper, 'remove_by_google_id' ] );
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 */
	protected function handle_complete( int $final_batch_number ) {
		$this->options->delete( OptionsInterface::GOOGLE_IDS_TO_CLEANUP );
	}
}
