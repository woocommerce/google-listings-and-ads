<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler;

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
	 * @param int    $timestamp When the job will run.
	 * @param string $hook      The hook to trigger.
	 * @param array  $args      Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function schedule_single( int $timestamp, string $hook, array $args = [] ): int {
		return as_schedule_single_action( $timestamp, $hook, $args, $this->get_slug() );
	}

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
	public function schedule_immediate( string $hook, array $args = [] ): int {
		return as_schedule_single_action( gmdate( 'U' ) - 1, $hook, $args, $this->get_slug() );
	}

	/**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param string $hook  The hook to trigger.
	 * @param array  $args  Arguments to pass when the hook triggers.
	 *
	 * @return int The action ID.
	 */
	public function enqueue_async_action( string $hook, array $args = [] ): int {
		$this->async_runner->attach_shutdown_hook();
		return $this->schedule_immediate( $hook, $args );
	}

	/**
	 * Check if there is an existing action in the queue with a given hook, args and group combination.
	 *
	 * An action in the queue could be pending, in-progress or async. If the is pending for a time in
	 * future, its scheduled date will be returned as a timestamp. If it is currently being run, or an
	 * async action sitting in the queue waiting to be processed, in which case boolean true will be
	 * returned. Or there may be no async, in-progress or pending action for this hook, in which case,
	 * boolean false will be the return value.
	 *
	 * @param string $hook
	 * @param array  $args
	 *
	 * @return int|bool The timestamp for the next occurrence of a pending scheduled action, true for an async or in-progress action or false if there is no matching action.
	 */
	public function next_scheduled_action( string $hook, array $args = [] ) {
		return as_next_scheduled_action( $hook, $args, $this->get_slug() );
	}

	/**
	 * Search for scheduled actions.
	 *
	 * @param array  $args          See as_get_scheduled_actions() for possible arguments.
	 * @param string $return_format OBJECT, ARRAY_A, or ids.
	 *
	 * @return array
	 */
	public function search( array $args = [], $return_format = OBJECT ): array {
		$args['group'] = $this->get_slug();

		return as_get_scheduled_actions( $args, $return_format );
	}

	/**
	 * Cancel the next scheduled instance of an action with a matching hook (and optionally matching args and group).
	 *
	 * Any recurring actions with a matching hook should also be cancelled, not just the next scheduled action.
	 *
	 * @param string $hook  The hook that the job will trigger.
	 * @param array  $args  Args that would have been passed to the job.
	 *
	 * @return string|null The scheduled action ID if a scheduled action was found, or null if no matching action found.
	 */
	public function cancel( string $hook, array $args = [] ) {
		return as_unschedule_action( $hook, $args, $this->get_slug() );
	}

}
