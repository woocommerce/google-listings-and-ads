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
 * Uses keyset (cursor) pagination, like `ResubmitExpiringProducts`, rather
 * than `AbstractProductSyncerBatchedJob`'s OFFSET-based batch numbering: a
 * batch here is expected to mutate the very rows its own query matched (e.g.
 * deleting or unsyncing them), which shrinks the result set as the run
 * progresses. OFFSET-based paging would skip rows whenever that happens,
 * since each new page still skips a fixed count from the start rather than
 * resuming after the last row actually seen; a cursor is immune to that
 * because it only ever asks for rows after the highest ID already handled.
 *
 * `AbstractProductSyncerBatchedJob`'s batches are also always context-free (a
 * plain scan of the whole catalogue), so its `schedule()` drops any `$args`
 * it's given and its `create_batch`/`process_item` hooks only ever carry a
 * batch number or an item list. This class re-implements that same batch
 * cycle, but threads a `$context` array alongside the cursor and items at
 * every step, so `get_batch()` and `process_items()` can use it.
 *
 * `handle_create_batch_action()` and `handle_process_items_action()` below
 * therefore take an extra `$context` parameter beyond what
 * `BatchedActionSchedulerJobInterface` declares for them. PHP allows this
 * (an override may add optional parameters), but it means the interface
 * alone no longer fully describes this class's hook contract.
 *
 * @since 3.9.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractContextualProductSyncerBatchedJob extends AbstractProductSyncerBatchedJob {

	/**
	 * Init the batch schedule for the job.
	 *
	 * Deliberately does not call `parent::init()`: the parent registers both
	 * hooks at arity 1 (batch number / items only), and this class's handlers
	 * need arity 2 to also receive `$context`. Calling both would register
	 * `handle_create_batch_action()`/`handle_process_items_action()` twice.
	 */
	public function init(): void {
		add_action( $this->get_create_batch_hook(), [ $this, 'handle_create_batch_action' ], 10, 2 );
		add_action( $this->get_process_item_hook(), [ $this, 'handle_process_items_action' ], 10, 2 );
	}

	/**
	 * Enqueue the "create_batch" action, starting the cursor at 0, provided it doesn't already exist.
	 *
	 * @param array $context The run's context, carried through every batch of this run.
	 */
	public function schedule( array $context = [] ) {
		$this->schedule_create_batch_action( 0, $context );
	}

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/create_batch
	 *
	 * @param int   $last_id The cursor: the highest product ID processed so far (0 on first run).
	 * @param array $context The run's context, as passed to `schedule()`.
	 *
	 * @throws Exception If an error occurs.
	 * @throws JobException If the job failure rate is too high.
	 */
	public function handle_create_batch_action( int $last_id, array $context = [] ) {
		$create_batch_hook = $this->get_create_batch_hook();
		$create_batch_args = [ $last_id, $context ];

		$this->monitor->validate_failure_rate( $this, $create_batch_hook, $create_batch_args );
		if ( $this->retry_on_timeout ) {
			$this->monitor->attach_timeout_monitor( $create_batch_hook, $create_batch_args );
		}

		$items = $this->get_batch( $last_id, $context );

		if ( empty( $items ) ) {
			// if no more items the job is complete. Note $last_id is a cursor here, not a batch
			// count, despite handle_complete()'s inherited "$final_batch_number" parameter name.
			$this->handle_complete( $last_id );
		} else {
			// if items, schedule the process action
			$this->schedule_process_action( $items, $context );

			// Advance the cursor to the highest ID seen in this batch. The last batch
			// created here will come back empty, and will call "handle_complete" to
			// finish the job.
			$this->schedule_create_batch_action( max( $items ), $context );
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
	 * @param int   $last_id The cursor for the new batch.
	 * @param array $context The run's context, as passed to `schedule()`.
	 */
	protected function schedule_create_batch_action( int $last_id, array $context = [] ) {
		$args = [ $last_id, $context ];
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
	 * @param int   $last_id The cursor: fetch items with ID strictly greater than this value.
	 * @param array $context The run's context, as passed to `schedule()`.
	 *
	 * @return array
	 *
	 * @throws Exception If an error occurs. The exception will be logged by ActionScheduler.
	 */
	abstract protected function get_batch( int $last_id, array $context = [] ): array;

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
