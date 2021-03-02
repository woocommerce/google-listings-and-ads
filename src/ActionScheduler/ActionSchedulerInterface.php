<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ActionSchedulerInterface
 *
 * Acts as a wrapper for ActionScheduler's public functions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler
 */
interface ActionSchedulerInterface {

	public const STATUS_COMPLETE = 'complete';
	public const STATUS_PENDING  = 'pending';
	public const STATUS_RUNNING  = 'in-progress';
	public const STATUS_FAILED   = 'failed';
	public const STATUS_CANCELED = 'canceled';

	/**
	 * Schedule an action to run once at some time in the future
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function schedule_single( int $timestamp, string $hook, array $args = [] ): int;

	/**
	 * Schedule an action to run now i.e. in the next available batch.
	 *
	 * This differs from async actions by having a scheduled time rather than being set for '0000-00-00 00:00:00'.
	 * We could use an async action instead but they can't be viewed easily in the admin area
	 * because the table is sorted by schedule date.
	 *
	 * @param string $hook  The hook to trigger.
	 * @param array  $args  Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function schedule_immediate( string $hook, array $args = [] ): int;

	/**
	 * Schedule a recurring action to run now (i.e. in the next available batch), and in the given intervals.
	 *
	 * @param int    $timestamp           When the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook                The hook to trigger.
	 * @param array  $args                Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function schedule_recurring( int $timestamp, int $interval_in_seconds, string $hook, array $args = [] ): int;

	/**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param string $hook  The hook to trigger.
	 * @param array  $args  Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function enqueue_async_action( string $hook, array $args = [] ): int;

	/**
	 * Check if there is an existing action in the queue with a given hook and args combination.
	 *
	 * An action in the queue could be pending, in-progress or async. If the action is pending for a time in
	 * future, currently being run, or an async action sitting in the queue waiting to be processed, boolean
	 * true will be returned. Or there may be no async, in-progress or pending action for this hook, in which
	 * case, boolean false will be the return value.
	 *
	 * @param string $hook
	 * @param array  $args
	 *
	 * @return bool True if there is a pending scheduled, async or in-progress action in the queue or false if there is no matching action.
	 */
	public function has_scheduled_action( string $hook, array $args = [] ): bool;

	/**
	 * Search for scheduled actions.
	 *
	 * @param array  $args          See as_get_scheduled_actions() for possible arguments.
	 * @param string $return_format OBJECT, ARRAY_A, or ids.
	 *
	 * @return array
	 */
	public function search( array $args = [], $return_format = OBJECT ): array;

	/**
	 * Cancel the next scheduled instance of an action with a matching hook (and optionally matching args).
	 *
	 * Any recurring actions with a matching hook should also be cancelled, not just the next scheduled action.
	 *
	 * @param string $hook  The hook that the job will trigger.
	 * @param array  $args  Args that would have been passed to the job.
	 *
	 * @return string The scheduled action ID if a scheduled action was found.
	 *
	 * @throws ActionSchedulerException If no matching action found.
	 */
	public function cancel( string $hook, array $args = [] ): string;

}
