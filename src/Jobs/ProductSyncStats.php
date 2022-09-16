<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductSyncStats
 *
 * Counts how many scheduled jobs we have for syncing products.
 * A scheduled job can either be a batch or an individual product.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class ProductSyncStats {

	/**
	 * The ActionScheduler object.
	 *
	 * @var ActionScheduler
	 */
	protected $scheduler;

	/**
	 * The ProductRepository object.
	 *
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * Job names for syncing products.
	 */
	protected const MATCHES = [
		'refresh_synced_products',
		'update_all_products',
		'update_products',
		'delete_products',
	];

	/**
	 * Batch size for retrieving product count.
	 */
	protected const BATCH_SIZE = 500;

	/**
	 * ProductSyncStats constructor.
	 *
	 * @param ActionScheduler   $scheduler
	 * @param ProductRepository $product_repository
	 */
	public function __construct( ActionScheduler $scheduler, ProductRepository $product_repository ) {
		$this->scheduler          = $scheduler;
		$this->product_repository = $product_repository;
	}

	/**
	 * Check if a job name is used for product syncing.
	 *
	 * @param string $hook
	 *
	 * @return bool
	 */
	protected function job_matches( string $hook ): bool {
		foreach ( self::MATCHES as $match ) {
			if ( false !== stripos( $hook, $match ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the amount of product sync jobs which are pending.
	 *
	 * @return int
	 */
	public function get_count(): int {
		$count     = 0;
		$scheduled = $this->scheduler->search(
			[
				'status'   => 'pending',
				'per_page' => -1,
			]
		);

		foreach ( $scheduled as $action ) {
			if ( $this->job_matches( $action->get_hook() ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Return the amount of products which are ready to be synced.
	 *
	 * @since x.x.x
	 *
	 * @return int
	 */
	public function get_syncable_products_count(): int {
		$offset = 0;
		$count  = 0;

		// Count products in batches as product filtering requires the full product object.
		// Without batches larger sites would run into resource limitations.
		do {
			$result = $this->product_repository->find_sync_ready_products( [], self::BATCH_SIZE, $offset );

			$offset += $result->get_unfiltered_count();
			$count  += $result->count();
		} while ( $result->get_unfiltered_count() === self::BATCH_SIZE );

		return $count;
	}
}
