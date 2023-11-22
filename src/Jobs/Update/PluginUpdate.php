<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;

defined( 'ABSPATH' ) || exit;

/**
 * Runs update jobs when the plugin is updated.
 *
 * @since 1.1.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update
 */
class PluginUpdate implements Service, InstallableInterface {

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * PluginUpdate constructor.
	 *
	 * @param JobRepository $job_repository
	 */
	public function __construct( JobRepository $job_repository ) {
		$this->job_repository = $job_repository;
	}

	/**
	 * Update Jobs that need to be run per version.
	 *
	 * @var array
	 */
	private $updates = [
		'1.0.1'  => [
			CleanupProductTargetCountriesJob::class,
			UpdateAllProducts::class,
		],
		'1.12.6' => [
			UpdateAllProducts::class,
		],
	];

	/**
	 * Run installation logic for this class.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void {
		foreach ( $this->updates as $version => $jobs ) {
			if ( version_compare( $old_version, $version, '<' ) ) {
				$this->schedule_jobs( $jobs );
			}
		}
	}

	/**
	 * Schedules a list of jobs.
	 *
	 * @param array $jobs List of jobs
	 */
	protected function schedule_jobs( array $jobs ): void {
		foreach ( $jobs as $job ) {
			try {
				$this->job_repository->get( $job )->schedule();
			} catch ( JobException $e ) {
				do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			}
		}
	}
}
