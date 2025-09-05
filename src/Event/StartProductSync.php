<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Event;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class StartProductSync
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Event
 */
class StartProductSync implements Registerable, Service {
	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * StartProductSync constructor.
	 *
	 * @param JobRepository $job_repository
	 */
	public function __construct( JobRepository $job_repository ) {
		$this->job_repository = $job_repository;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'woocommerce_gla_mc_settings_sync',
			function () {
				$this->on_settings_sync();
			}
		);

		add_action(
			'woocommerce_gla_mapping_rules_change',
			function () {
				$this->on_rules_change();
			}
		);

		add_action(
			'woocommerce_gla_sync_mode_updated',
			function ( $prev_sync_mode, $sync_mode ) {
				$this->on_sync_mode_updated( $prev_sync_mode, $sync_mode );
			},
			10,
			2
		);
	}

	/**
	 * Start the cleanup and update all products.
	 */
	protected function on_settings_sync() {
		$cleanup = $this->job_repository->get( CleanupProductsJob::class );
		$cleanup->schedule();

		$update = $this->job_repository->get( UpdateAllProducts::class );
		$update->schedule();
	}

	/**
	 * Creates a Job for updating all products with a 30 minutes delay.
	 */
	protected function on_rules_change() {
		$update = $this->job_repository->get( UpdateAllProducts::class );
		$update->schedule_delayed( 1800 ); // 30 minutes
	}

	/**
	 * If the Push mode of product sync is switched to enable, schedule a job to sync all products.
	 *
	 * @param array $prev_sync_mode The previous sync mode.
	 * @param array $sync_mode The current sync mode.
	 */
	protected function on_sync_mode_updated( $prev_sync_mode, $sync_mode ) {
		// It's possible that the incoming modes don't have the expected structure
		// for example, due to the `woocommerce_gla_sync_mode` filter.
		try {
			$prev_push    = $prev_sync_mode['products']['push'] ?? null;
			$current_push = $sync_mode['products']['push'] ?? null;
		} catch ( Throwable $e ) {
			do_action(
				'woocommerce_gla_debug_message',
				'One or more of the incoming sync mode structures are invalid.',
				__METHOD__
			);
			return;
		}

		if ( $prev_push === false && $current_push === true ) {
			$update = $this->job_repository->get( UpdateAllProducts::class );
			$update->schedule();
		}
	}
}
