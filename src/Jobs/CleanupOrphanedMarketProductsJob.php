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
 * Deletes Merchant Center entries that belong to a removed or renamed market's
 * `feed_label`. Scheduled by MarketService when a market is deleted or its
 * `feed_label` changes; carries the previous `feed_label` so the orphaned
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
	 * @param array $args Accepts `[ 'feed_label' => 'XX' ]`.
	 *
	 * @throws InvalidValue When `feed_label` is missing or empty.
	 */
	public function schedule( array $args = [] ) {
		$feed_label = $args['feed_label'] ?? null;

		if ( ! is_string( $feed_label ) || '' === $feed_label ) {
			throw InvalidValue::is_empty( 'feed_label' );
		}

		$process_args = [ [ $feed_label ] ];

		if ( $this->can_schedule( $process_args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), $process_args );
		}
	}

	/**
	 * Process the orphaned market's products.
	 *
	 * @param array $items Single-element array containing the feed_label to clean up.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_items( array $items ) {
		$feed_label = $items[0] ?? null;

		if ( ! is_string( $feed_label ) || '' === $feed_label ) {
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
			if ( empty( $google_ids[ $feed_label ] ) ) {
				continue;
			}

			$google_id                     = $google_ids[ $feed_label ];
			$request_entries[ $google_id ] = new BatchProductIDRequestEntry( $product->get_id(), $google_id );
		}

		if ( empty( $request_entries ) ) {
			return;
		}

		$response = $this->product_syncer->delete_by_batch_requests( $request_entries );

		foreach ( $response->get_products() as $deleted ) {
			$google_product = $deleted->get_google_product();
			if ( null === $google_product ) {
				continue;
			}

			try {
				$product = $this->product_helper->get_wc_product( $deleted->get_wc_product_id() );
			} catch ( InvalidValue $exception ) {
				continue;
			}

			$this->product_helper->remove_google_id( $product, $google_product->getId() );
		}
	}
}
