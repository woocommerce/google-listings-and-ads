<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class JobInitializer
 *
 * Initializes all jobs when certain conditions are met (e.g. the request is async or initiated by CRON, CLI, etc.).
 *
 * The list of jobs (classes implementing JobInterface) are pulled from the container.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class JobInitializer implements Service, Registerable, Conditional {

	/**
	 * @var JobInterface[]
	 */
	protected $jobs;

	/**
	 * JobInitializer constructor.
	 *
	 * @param JobInterface[] $jobs
	 *
	 * @throws InvalidClass    When any of the given job classes do not implement the JobInterface.
	 * @throws InvalidArgument When any of the given job classes is not an object.
	 */
	public function __construct( array $jobs ) {
		array_walk(
			$jobs,
			function ( $job ) {
				if ( ! is_object( $job ) ) {
					throw InvalidArgument::not_object( 'jobs', __METHOD__ );
				}
				if ( ! $job instanceof JobInterface ) {
					throw InvalidClass::should_implement( get_class( $job ), JobInterface::class );
				}
			}
		);

		$this->jobs = $jobs;
	}

	/**
	 * Initialize all jobs.
	 */
	public function register(): void {
		foreach ( $this->jobs as $job ) {
			$job->init();
		}
	}

	/**
	 * Check whether this object is currently needed.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) || ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() );
	}
}
