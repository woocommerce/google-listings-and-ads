<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler;

use ActionScheduler as ActionSchedulerCore;
use ActionScheduler_Action;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * ActionScheduler service class.
 *
 * Acts as a wrapper for ActionScheduler's public functions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler
 */
class ActionScheduler implements ActionSchedulerInterface, Service {

	use PluginHelper;

	/**
	 * @var AsyncActionRunner
	 */
	protected $async_runner;

	/**
	 * ActionScheduler constructor.
	 *
	 * @param AsyncActionRunner $async_runner
	 */
	public function __construct( AsyncActionRunner $async_runner ) {
		$this->async_runner = $async_runner;
	}

	/**
	 * Schedule an action to run once at some time in the future
	 *
	 * @param int        $timestamp When the job will run.
	 * @param string     $hook      The hook to trigger.
	 * @param array|null $args      Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function schedule_single( int $timestamp, string $hook, $args = [] ): int {
		return as_schedule_single_action( $timestamp, $hook, $args, $this->get_slug() );
	}

	/**
	 * Schedule an action to run now i.e. in the next available batch.
	 *
	 * This differs from async actions by having a scheduled time rather than being set for '0000-00-00 00:00:00'.
	 * We could use an async action instead but they can't be viewed easily in the admin area
	 * because the table is sorted by schedule date.
	 *
	 * @param string     $hook The hook to trigger.
	 * @param array|null $args Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function schedule_immediate( string $hook, $args = [] ): int {
		return as_schedule_single_action( gmdate( 'U' ) - 1, $hook, $args, $this->get_slug() );
	}

	/**
	 * Schedule a recurring action to run now (i.e. in the next available batch), and in the given intervals.
	 *
	 * @param int        $timestamp           When the job will run.
	 * @param int        $interval_in_seconds How long to wait between runs.
	 * @param string     $hook                The hook to trigger.
	 * @param array|null $args                Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function schedule_recurring( int $timestamp, int $interval_in_seconds, string $hook, $args = [] ): int {
		return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $this->get_slug() );
	}

	/**
	 * Schedule an action that recurs on a cron-like schedule.
	 *
	 * @param int        $timestamp The first instance of the action will be scheduled to run at a time
	 *                              calculated after this timestamp matching the cron expression. This
	 *                              can be used to delay the first instance of the action.
	 * @param string     $schedule  A cron-link schedule string
	 * @param string     $hook      The hook to trigger.
	 * @param array|null $args      Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 *
	 * @see https://en.wikipedia.org/wiki/Cron
	 *   *    *    *    *    *    *
	 *   ┬    ┬    ┬    ┬    ┬    ┬
	 *   |    |    |    |    |    |
	 *   |    |    |    |    |    + year [optional]
	 *   |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
	 *   |    |    |    +---------- month (1 - 12)
	 *   |    |    +--------------- day of month (1 - 31)
	 *   |    +-------------------- hour (0 - 23)
	 *   +------------------------- min (0 - 59)
	 */
	public function schedule_cron( int $timestamp, string $schedule, string $hook, $args = [] ): int {
		return as_schedule_cron_action( $timestamp, $schedule, $hook, $args, $this->get_slug() );
	}

	/**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param string     $hook The hook to trigger.
	 * @param array|null $args Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function enqueue_async_action( string $hook, $args = [] ): int {
		$this->async_runner->attach_shutdown_hook();
		return $this->schedule_immediate( $hook, $args );
	}

	/**
	 * Check if there is an existing action in the queue with a given hook and args combination.
	 *
	 * An action in the queue could be pending, in-progress or async. If the action is pending for a time in
	 * future, currently being run, or an async action sitting in the queue waiting to be processed, boolean
	 * true will be returned. Or there may be no async, in-progress or pending action for this hook, in which
	 * case, boolean false will be the return value.
	 *
	 * @param string     $hook
	 * @param array|null $args
	 *
	 * @return bool True if there is a pending scheduled, async or in-progress action in the queue or false if there is no matching action.
	 */
	public function has_scheduled_action( string $hook, $args = [] ): bool {
		return ( false !== as_next_scheduled_action( $hook, $args, $this->get_slug() ) );
	}

	/**
	 * Search for scheduled actions.
	 *
	 * @param array|null $args          See as_get_scheduled_actions() for possible arguments.
	 * @param string     $return_format OBJECT, ARRAY_A, or ids.
	 *
	 * @return array
	 */
	public function search( $args = [], $return_format = OBJECT ): array {
		$args['group'] = $this->get_slug();

		return as_get_scheduled_actions( $args, $return_format );
	}

	/**
	 * Cancel the next scheduled instance of an action with a matching hook (and optionally matching args).
	 *
	 * Any recurring actions with a matching hook should also be cancelled, not just the next scheduled action.
	 *
	 * @param string     $hook The hook that the job will trigger.
	 * @param array|null $args Args that would have been passed to the job.
	 *
	 * @return int The scheduled action ID if a scheduled action was found.
	 *
	 * @throws ActionSchedulerException If no matching action found.
	 */
	public function cancel( string $hook, $args = [] ) {
		$action_id = as_unschedule_action( $hook, $args, $this->get_slug() );

		if ( null === $action_id ) {
			throw ActionSchedulerException::action_not_found( $hook );
		}

		return $action_id;
	}

	/**
	 * Retrieve an action.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @return ActionScheduler_Action
	 *
	 * @since 1.7.0
	 */
	public function fetch_action( int $action_id ): ActionScheduler_Action {
		return ActionSchedulerCore::store()->fetch_action( $action_id );
	}

}
