<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class ActionSchedulerJobMonitor
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class ActionSchedulerJobMonitor implements Service {

	use PluginHelper;

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * ActionSchedulerInterface constructor.
	 *
	 * @param ActionSchedulerInterface $action_scheduler
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler ) {
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Check whether the failure rate is above a threshold within the last hour.
	 *
	 * To protect against failing jobs running forever the job's failure rate is checked before creating a new batch.
	 * By default, a job is stopped if it has 5 failures in the last hour.
	 *
	 * @param ActionSchedulerJobInterface $job
	 * @param string                      $hook The job action hook.
	 * @param array|null                  $args The job arguments.
	 *
	 * @throws JobException If the job's error rate is above the threshold.
	 */
	public function validate_failure_rate( ActionSchedulerJobInterface $job, string $hook, ?array $args = null ) {
		$failed_actions = $this->action_scheduler->search(
			[
				'hook'         => $hook,
				'args'         => $args,
				'status'       => $this->action_scheduler::STATUS_FAILED,
				'per_page'     => $this->get_failure_rate_threshold(),
				'date'         => gmdate( 'U' ) - $this->get_failure_timeframe(),
				'date_compare' => '>',
			],
			'ids'
		);

		if ( count( $failed_actions ) === $this->get_failure_rate_threshold() ) {
			throw JobException::stopped_due_to_high_failure_rate( $job->get_name() );
		}
	}

	/**
	 * Get the job failure rate threshold (per timeframe).
	 *
	 * @return int
	 */
	protected function get_failure_rate_threshold(): int {
		return absint( apply_filters( 'woocommerce_gla_job_failure_rate_threshold', 3 ) );
	}

	/**
	 * Get the job failure timeframe (in seconds).
	 *
	 * @return int
	 */
	protected function get_failure_timeframe(): int {
		return absint( apply_filters( 'woocommerce_gla_job_failure_timeframe', 2 * HOUR_IN_SECONDS ) );
	}

}
