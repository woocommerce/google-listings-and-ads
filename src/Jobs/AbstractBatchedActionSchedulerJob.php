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
		add_action( $this->get_create_batch_hook(), [ $this, 'handle_create_batch_action' ] );
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
	public function start( array $args = [] ) {
		$this->schedule_create_batch_action( 1 );
	}

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/create_batch
	 *
	 * Schedules an action to run immediately for the items in the batch.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @throws Exception If an error occurs.
	 * @throws JobException If the job failure rate is too high.
	 */
	public function handle_create_batch_action( int $batch_number ) {
		$this->monitor->validate_failure_rate( $this );

		$items = $this->get_batch( $batch_number );

		if ( empty( $items ) ) {
			// if no more items the job is complete
			$this->handle_complete( $batch_number );
		} else {
			// if items, schedule the process action
			$this->schedule_process_action( $items );

			if ( count( $items ) >= $this->get_batch_size() ) {
				// if there might be more items, add another "create_batch" action to handle them
				$this->schedule_create_batch_action( $batch_number + 1 );
			}
		}
	}

	/**
	 * Get job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		return 100;
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
	 * @param int $batch_number The batch number for the new batch.
	 */
	protected function schedule_create_batch_action( int $batch_number ) {
		if ( $this->can_start( [ $batch_number ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_create_batch_hook(), [ $batch_number ] );
		}
	}

	/**
	 * Schedule a new "process" action to run immediately.
	 *
	 * @param array $items
	 */
	protected function schedule_process_action( array $items ) {
		if ( ! $this->is_processing( $items ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $items ] );
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
	protected function is_running( $args = [] ): bool {
		return $this->action_scheduler->has_scheduled_action( $this->get_create_batch_hook(), $args );
	}

	/**
	 * Check if this job is processing the given items.
	 *
	 * The job is considered to be processing if a "process_item" action is currently pending or in-progress.
	 *
	 * @param array $items
	 *
	 * @return bool
	 */
	protected function is_processing( array $items = [] ): bool {
		return $this->action_scheduler->has_scheduled_action( $this->get_process_item_hook(), [ $items ] );
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
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return array
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function get_batch( int $batch_number ): array;

}
