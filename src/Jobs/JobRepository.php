<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class JobRepository
 *
 * ContainerAware used for:
 * - JobInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class JobRepository implements ContainerAwareInterface, Service {

	use ContainerAwareTrait;

	/**
	 * @var JobInterface[] indexed by class name.
	 */
	protected $jobs = [];

	/**
	 * Fetch all jobs from Container.
	 *
	 * @return JobInterface[]
	 */
	public function list(): array {
		foreach ( $this->container->get( JobInterface::class ) as $job ) {
			$this->jobs[ get_class( $job ) ] = $job;
		}

		return $this->jobs;
	}

	/**
	 * Fetch job from Container (or cache if previously fetched).
	 *
	 * @param string $classname Job class name.
	 *
	 * @return JobInterface
	 *
	 * @throws JobException If the job is not found.
	 */
	public function get( string $classname ): JobInterface {
		if ( ! isset( $this->jobs[ $classname ] ) ) {
			try {
				$job = $this->container->get( $classname );
			} catch ( Exception $e ) {
				throw JobException::job_does_not_exist( $classname );
			}

			$classname                = get_class( $job );
			$this->jobs[ $classname ] = $job;
		}

		return $this->jobs[ $classname ];
	}
}
