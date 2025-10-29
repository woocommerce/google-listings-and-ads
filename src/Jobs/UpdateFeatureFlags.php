<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\StartOnHookInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WCS\ConnectionService;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateFeatureFlags
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateFeatureFlags extends AbstractActionSchedulerJob implements RecurringJobInterface, StartOnHookInterface {
	/**
	 * @var ConnectionService
	 */
	protected $connection;

	/**
	 * UpdateFeatureFlags constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ConnectionService         $connection
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ConnectionService $connection
	) {
		parent::__construct( $action_scheduler, $monitor );
		$this->connection = $connection;
	}

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( "{$this->get_hook_base_name()}start" );
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int
	 */
	public function get_interval(): int {
		return HOUR_IN_SECONDS; // 1 hour.
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_wcs_feature_flags';
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return parent::can_schedule( $args );
	}

	/**
	 * Process the job.
	 *
	 * @param int[] $items An array of job arguments.
	 *
	 * @throws JobException If the merchant product statuses cannot be retrieved..
	 */
	public function process_items( array $items ) {
		try {
			// Process to update the feature flags option.
			$this->connection->update_feature_flags();
		} catch ( Throwable $e ) {
			throw new JobException( 'Error updating WCS feature flags: ' . $e->getMessage() );
		}
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args - arguments.
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule( $args ) ) {
			$this->action_scheduler->schedule_recurring( time(), $this->get_interval(), $this->get_process_item_hook(), $args );
		}
	}

	/**
	 * The job is considered to be scheduled if the "process_item" action is currently pending or in-progress regardless of the arguments.
	 *
	 * @return bool
	 */
	public function is_scheduled(): bool {
		// We set 'args' to null so it matches any arguments. This is because it's possible to have multiple instances of the job running with different page tokens
		return $this->is_running( null );
	}
}
