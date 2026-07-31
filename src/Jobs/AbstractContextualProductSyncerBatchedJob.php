<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractContextualProductSyncerBatchedJob
 *
 * A batched product syncer job whose run needs a fixed piece of context
 * established when the job is scheduled (e.g. which feed labels or languages
 * to clean up), carried through every batch of that run.
 *
 * `AbstractProductSyncerBatchedJob`'s batches are always context-free (a
 * plain paginated scan of the whole catalogue), so its `schedule()` drops any
 * `$args` it's given and its `create_batch`/`process_item` hooks only ever
 * carry a batch number or an item list. This class re-implements that same
 * batch cycle, but threads a `$context` array alongside the batch number and
 * items at every step, so `get_batch()` and `process_items()` can use it.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractContextualProductSyncerBatchedJob extends AbstractProductSyncerBatchedJob {

	/**
	 * Init the batch schedule for the job.
	 */
	public function init(): void {
		add_action( $this->get_create_batch_hook(), [ $this, 'handle_create_batch_action' ], 10, 2 );
		add_action( $this->get_process_item_hook(), [ $this, 'handle_process_items_action' ], 10, 2 );
	}

	/**
	 * Enqueue the "create_batch" action provided it doesn't already exist.
	 *
	 * @param array $context The run's context, carried through every batch of this run.
	 */
	public function schedule( array $context = [] ) {
		$this->schedule_create_batch_action( 1, $context );
	}

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/create_batch
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $context      The run's context, as passed to `schedule()`.
	 *
	 * @throws Exception If an error occurs.
	 * @throws JobException If the job failure rate is too high.
	 */
	public function handle_create_batch_action( int $batch_number, array $context = [] ) {
		$create_batch_hook = $this->get_create_batch_hook();
		$create_batch_args = [ $batch_number, $context ];

		$this->monitor->validate_failure_rate( $this, $create_batch_hook, $create_batch_args );
		if ( $this->retry_on_timeout ) {
			$this->monitor->attach_timeout_monitor( $create_batch_hook, $create_batch_args );
		}

		$items = $this->get_batch( $batch_number, $context );

		if ( empty( $items ) ) {
			// if no more items the job is complete
			$this->handle_complete( $batch_number );
		} else {
			// if items, schedule the process action
			$this->schedule_process_action( $items, $context );

			// Add another "create_batch" action to handle unfiltered items.
			// The last batch created here will be an empty batch, it
			// will call "handle_complete" to finish the job.
			$this->schedule_create_batch_action( $batch_number + 1, $context );
		}

		$this->monitor->detach_timeout_monitor( $create_batch_hook, $create_batch_args );
	}

	/**
	 * Handles processing single item action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/process_item
	 *
	 * @param array $items   The job items from the current batch.
	 * @param array $context The run's context, as passed to `schedule()`.
	 *
	 * @throws Exception If an error occurs.
	 */
	public function handle_process_items_action( array $items = [], array $context = [] ) {
		$process_hook = $this->get_process_item_hook();
		$process_args = [ $items, $context ];

		$this->monitor->validate_failure_rate( $this, $process_hook, $process_args );
		if ( $this->retry_on_timeout ) {
			$this->monitor->attach_timeout_monitor( $process_hook, $process_args );
		}

		try {
			$this->process_items( $items, $context );
		} catch ( Exception $exception ) {
			// reschedule on failure
			$this->action_scheduler->schedule_immediate( $process_hook, $process_args );

			// throw the exception again so that it can be logged
			throw $exception;
		}

		$this->monitor->detach_timeout_monitor( $process_hook, $process_args );
	}

	/**
	 * Schedule a new "create batch" action to run immediately.
	 *
	 * @param int   $batch_number The batch number for the new batch.
	 * @param array $context      The run's context, as passed to `schedule()`.
	 */
	protected function schedule_create_batch_action( int $batch_number, array $context = [] ) {
		$args = [ $batch_number, $context ];
		if ( $this->can_schedule( $args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_create_batch_hook(), $args );
		}
	}

	/**
	 * Schedule a new "process" action to run immediately.
	 *
	 * @param array $items   Array of item ids.
	 * @param array $context The run's context, as passed to `schedule()`.
	 */
	protected function schedule_process_action( array $items, array $context = [] ) {
		$args = [ $items, $context ];
		if ( ! $this->action_scheduler->has_scheduled_action( $this->get_process_item_hook(), $args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), $args );
		}
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $context      The run's context, as passed to `schedule()`.
	 *
	 * @return array
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function get_batch( int $batch_number, array $context = [] ): array;

	/**
	 * Process batch items.
	 *
	 * @param array $items   A single batch from the get_batch() method.
	 * @param array $context The run's context, as passed to `schedule()`.
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function process_items( array $items, array $context = [] );
}
