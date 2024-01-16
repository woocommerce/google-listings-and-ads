<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractBatchedActionSchedulerJob class.
 *
 * Enables a job to be processed in recurring scheduled batches with queued events.
 *
 * Notes:
 * - Uses ActionScheduler's very scalable async actions feature which will run async batches in loop back requests until all batches are done
 * - Items may be processed concurrently by AS, but batches will be created one after the other, not concurrently
 * - The job will not start if it is already running
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractBatchedActionSchedulerJob extends AbstractActionSchedulerJob implements BatchedActionSchedulerJobInterface {

	/**
	 * Init the batch schedule for the job.
	 *
	 * The job name is used to generate the schedule event name.
	 */
	public function init(): void {
		add_action( $this->get_create_batch_hook(), [ $this, 'handle_create_batch_action' ], 10, 2 );
		parent::init();
	}

	/**
	 * Get the hook name for the "create batch" action.
	 *
	 * @return string
	 */
	protected function get_create_batch_hook(): string {
		return "{$this->get_hook_base_name()}create_batch";
	}

	/**
	 * Enqueue the "create_batch" action provided it doesn't already exist.
	 *
	 * To minimize the resource use of starting the job the batch creation is handled async.
	 *
	 * @param array $args
	 */
	public function schedule( array $args = [] ) {
		$this->schedule_create_batch_action( 1 );
	}

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/create_batch
	 *
	 * Schedules an action to run immediately for the items in the batch.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args The action arguments.
	 *
	 * @throws Exception If an error occurs.
	 * @throws JobException If the job failure rate is too high.
	 */
	public function handle_create_batch_action( int $batch_number, array $args = [] ) {
		$create_batch_hook = $this->get_create_batch_hook();
		$create_batch_args = [ $batch_number, $args ];

		$this->monitor->validate_failure_rate( $this, $create_batch_hook, $create_batch_args );
		if ( $this->retry_on_timeout ) {
			$this->monitor->attach_timeout_monitor( $create_batch_hook, $create_batch_args );
		}

		$items = $this->get_batch( $batch_number, $args );

		if ( empty( $items ) ) {
			// if no more items the job is complete
			$this->handle_complete( $batch_number );
		} else {
			// if items, schedule the process action
			$this->schedule_process_action( $items, $args );

			// Add another "create_batch" action to handle unfiltered items.
			// The last batch created here will be an empty batch, it
			// will call "handle_complete" to finish the job.
			$this->schedule_create_batch_action( $batch_number + 1, $args );
		}

		$this->monitor->detach_timeout_monitor( $create_batch_hook, $create_batch_args );
	}

	/**
	 * Get job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		/**
		 * Filters the batch size for the job.
		 *
		 * @param string Job's name
		 */
		return apply_filters( 'woocommerce_gla_batched_job_size', 100, $this->get_name() );
	}

	/**
	 * Get the query offset based on a given batch number and the specified batch size.
	 *
	 * @param int $batch_number
	 *
	 * @return int
	 */
	protected function get_query_offset( int $batch_number ): int {
		return $this->get_batch_size() * ( $batch_number - 1 );
	}

	/**
	 * Schedule a new "create batch" action to run immediately.
	 *
	 * @param int   $batch_number The batch number for the new batch.
	 * @param array $args The action arguments.
	 */
	protected function schedule_create_batch_action( int $batch_number, array $args = [] ) {
		if ( $this->can_schedule( [ $batch_number, $args ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_create_batch_hook(), [ $batch_number, $args ] );
		}
	}

	/**
	 * Schedule a new "process" action to run immediately.
	 *
	 * @param int[] $items Array of item ids.
	 * @param array $args The action arguments.
	 */
	protected function schedule_process_action( array $items, array $args = [] ) {
		if ( ! $this->is_processing( [ $items, $args ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $items, $args ] );
		}
	}

	/**
	 * Check if this job is running.
	 *
	 * The job is considered to be running if a "create_batch" action is currently pending or in-progress.
	 *
	 * @param array|null $args
	 *
	 * @return bool
	 */
	protected function is_running( ?array $args = [] ): bool {
		return $this->action_scheduler->has_scheduled_action( $this->get_create_batch_hook(), $args );
	}

	/**
	 * Check if this job is processing the given items.
	 *
	 * The job is considered to be processing if a "process_item" action is currently pending or in-progress.
	 *
	 * @param array $args The action arguments.
	 *
	 * @return bool
	 */
	protected function is_processing( array $args = [] ): bool {
		return $this->action_scheduler->has_scheduled_action( $this->get_process_item_hook(), $args );
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                  If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		// Optionally over-ride this method in child class.
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args The action arguments.
	 *
	 * @return array
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function get_batch( int $batch_number, array $args = [] ): array;
}
