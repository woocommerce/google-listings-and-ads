<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractActionSchedulerJob
 *
 * Abstract class for jobs that use ActionScheduler.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractActionSchedulerJob implements ActionSchedulerJobInterface {

	use PluginHelper;

	/**
	 * @var ActionSchedulerInterface
	 */
	protected $action_scheduler;

	/**
	 * @var ActionSchedulerJobMonitor
	 */
	protected $monitor;

	/**
	 * AbstractActionSchedulerJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor ) {
		$this->action_scheduler = $action_scheduler;
		$this->monitor          = $monitor;
	}

	/**
	 * Init the batch schedule for the job.
	 *
	 * The job name is used to generate the schedule event name.
	 */
	public function register(): void {
		add_action( $this->get_process_item_hook(), [ $this, 'handle_process_items_action' ] );
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return ! $this->is_running( $args );
	}

	/**
	 * Handles processing single item action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/process_item
	 *
	 * @param array $items The job items from the current batch.
	 *
	 * @throws Exception If an error occurs.
	 */
	public function handle_process_items_action( array $items ) {
		$this->monitor->validate_failure_rate( $this );

		try {
			$this->process_items( $items );
		} catch ( Exception $exception ) {
			// reschedule on failure
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $items ] );

			// throw the exception again so that it can be logged
			throw $exception;
		}
	}

	/**
	 * Check if this job is running.
	 *
	 * The job is considered to be running if the "process_item" action is currently pending or in-progress.
	 *
	 * @param array|null $args
	 *
	 * @return bool
	 */
	protected function is_running( $args = [] ): bool {
		return $this->action_scheduler->has_scheduled_action( $this->get_process_item_hook(), $args );
	}

	/**
	 * Get the base name for the job's scheduled actions.
	 *
	 * @return string
	 */
	protected function get_hook_base_name(): string {
		return "{$this->get_slug()}/jobs/{$this->get_name()}/";
	}

	/**
	 * Get the hook name for the "process item" action.
	 *
	 * This method is required by the job monitor.
	 *
	 * @return string
	 */
	public function get_process_item_hook(): string {
		return "{$this->get_hook_base_name()}process_item";
	}

	/**
	 * Process batch items.
	 *
	 * @param array $items A single batch from the get_batch() method.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function process_items( array $items );
}
