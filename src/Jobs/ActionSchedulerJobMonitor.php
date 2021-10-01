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
	 * Calls a function if the job has failed due to timeout.
	 *
	 * @param callable $callback The function to be called on job timeout.
	 * @param array    $args     The parameters to be passed to the function, as an indexed array.
	 *
	 * @since x.x.x
	 */
	public function monitor_timeout( callable $callback, array $args = [] ) {
		add_action(
			'action_scheduler_unexpected_shutdown',
			function ( $action_id, $error ) use ( $callback, $args ) {
				if ( ! empty( $error ) && $this->is_timeout_error( $error ) ) {
					call_user_func_array( $callback, $args );
				}
			},
			10,
			2
		);
	}

	/**
	 * Determines whether the given error is an execution "timeout" error.
	 *
	 * @param array $error An associative array describing the error with keys "type", "message", "file" and "line".
	 *
	 * @return bool
	 *
	 * @link https://www.php.net/manual/en/function.error-get-last.php
	 *
	 * @since x.x.x
	 */
	protected function is_timeout_error( array $error ): bool {
		return isset( $error['type'] ) && $error['type'] === E_ERROR &&
			   isset( $error['message'] ) && strpos( $error ['message'], 'Maximum execution time' ) !== false;
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
