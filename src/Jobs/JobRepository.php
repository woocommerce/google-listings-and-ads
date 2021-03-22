<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class JobRepository
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class JobRepository implements Service {

	use ValidateInterface;

	/**
	 * @var JobInterface[]
	 */
	protected $jobs = [];

	/**
	 * @var string[]
	 */
	protected $jobs_class_name_map = [];

	/**
	 * JobRepository constructor.
	 *
	 * @param JobInterface[] $jobs
	 */
	public function __construct( array $jobs ) {
		foreach ( $jobs as $job ) {
			$this->validate_instanceof( $job, JobInterface::class );

			$job_name                                = $job->get_name();
			$job_class                               = get_class( $job );
			$this->jobs[ $job_name ]                 = $job;
			$this->jobs_class_name_map[ $job_class ] = $job_name;
		}
	}

	/**
	 * @return JobInterface[]
	 */
	public function list(): array {
		return $this->jobs;
	}

	/**
	 * @param string $name Job name or class
	 *
	 * @return JobInterface
	 *
	 * @throws JobException If the job is not found.
	 */
	public function get( string $name ): JobInterface {
		if ( ! isset( $this->jobs[ $name ] ) && ! empty( $this->jobs_class_name_map[ $name ] ) ) {
			$name = $this->jobs_class_name_map[ $name ];
		}

		if ( ! isset( $this->jobs[ $name ] ) ) {
			throw JobException::job_does_not_exist( $name );
		}

		return $this->jobs[ $name ];
	}
}
