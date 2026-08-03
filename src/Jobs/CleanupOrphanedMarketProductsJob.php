<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class CleanupOrphanedMarketProductsJob
 *
 * Deletes Merchant Center entries that belong to a removed or renamed market.
 * Scheduled by MarketService when a market is deleted or its `feed_label`
 * changes; carries every `google_ids` key the market's entries can be stored
 * under (its base feed label plus each per-language variant) so the orphaned
 * entries can be removed before the next product sync writes new ones.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class CleanupOrphanedMarketProductsJob extends AbstractProductSyncerJob {

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * CleanupOrphanedMarketProductsJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductSyncer             $product_syncer
	 * @param ProductRepository         $product_repository
	 * @param MerchantCenterService     $merchant_center
	 * @param ProductHelper             $product_helper
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ProductSyncer $product_syncer,
		ProductRepository $product_repository,
		MerchantCenterService $merchant_center,
		ProductHelper $product_helper
	) {
		parent::__construct( $action_scheduler, $monitor, $product_syncer, $product_repository, $merchant_center );
		$this->product_helper = $product_helper;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'cleanup_orphaned_market_products_job';
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args Accepts `[ 'feed_labels' => string[] ]`: every `google_ids`
	 *                    key belonging to the removed or renamed market (its base
	 *                    feed label plus each per-language variant).
	 *
	 * @throws InvalidValue When `feed_labels` is missing or empty.
	 */
	public function schedule( array $args = [] ) {
		$feed_labels = $args['feed_labels'] ?? null;

		if ( ! is_array( $feed_labels ) || empty( $feed_labels ) ) {
			throw InvalidValue::is_empty( 'feed_labels' );
		}

		$process_args = [ [ 'feed_labels' => array_values( $feed_labels ) ] ];

		if ( $this->can_schedule( $process_args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), $process_args );
		}
	}

	/**
	 * Process the orphaned market's products.
	 *
	 * @param array $items Single-element array containing the scheduling args.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_items( array $items ) {
		$feed_labels = is_array( $items['feed_labels'] ?? null ) ? $items['feed_labels'] : [];

		if ( empty( $feed_labels ) ) {
			return;
		}

		$product_ids = $this->product_repository->find_synced_product_ids();

		if ( empty( $product_ids ) ) {
			return;
		}

		$products        = $this->product_repository->find_by_ids( $product_ids );
		$request_entries = [];
		foreach ( $products as $product ) {
			$google_ids = $this->product_helper->get_synced_google_product_ids( $product );
			if ( empty( $google_ids ) ) {
				continue;
			}

			foreach ( $feed_labels as $feed_label ) {
				if ( empty( $google_ids[ $feed_label ] ) ) {
					continue;
				}

				$google_id                     = $google_ids[ $feed_label ];
				$request_entries[ $google_id ] = new BatchProductIDRequestEntry( $product->get_id(), $google_id );
			}
		}

		if ( empty( $request_entries ) ) {
			return;
		}

		// The delete call also removes each deleted entry's Google ID from the
		// product's tracked IDs, leaving other markets' entries untouched.
		$this->product_syncer->delete_by_batch_requests( $request_entries );
	}
}
