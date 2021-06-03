<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;

defined( 'ABSPATH' ) || exit;

/**
 * Class StartProductSync
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
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
			'gla_mc_settings_sync',
			function() {
				$this->on_settings_sync();
			}
		);
	}

	/**
	 * Start the cleanup and update all products.
	 */
	protected function on_settings_sync() {
		$cleanup = $this->job_repository->get( CleanupProductsJob::class );
		$cleanup->start();

		$update = $this->job_repository->get( UpdateAllProducts::class );
		$update->start();
	}
}
