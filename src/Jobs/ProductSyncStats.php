<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;

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
	 * Job names for syncing products.
	 */
	protected const MATCHES = [
		'refresh_synced_products',
		'update_all_products',
		'update_products',
		'delete_products',
	];

	/**
	 * ProductSyncStats constructor.
	 *
	 * @param ActionScheduler $scheduler
	 */
	public function __construct( ActionScheduler $scheduler ) {
		$this->scheduler = $scheduler;
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
				++$count;
			}
		}

		return $count;
	}
}
