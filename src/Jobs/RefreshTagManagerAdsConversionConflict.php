<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerException;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class RefreshTagManagerAdsConversionConflict
 *
 * Re-checks the connected Google Tag Manager container for a Google Ads conversion tag, so a tag
 * added or removed after connecting is picked up without the merchant visiting the settings.
 *
 * Note: The job only runs while a container is connected.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class RefreshTagManagerAdsConversionConflict extends AbstractActionSchedulerJob implements RecurringJobInterface {

	/**
	 * @var TagManagerConnection
	 */
	protected $connection;

	/**
	 * RefreshTagManagerAdsConversionConflict constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param TagManagerConnection      $connection
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		TagManagerConnection $connection
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
		return 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'refresh_tag_manager_ads_conversion_conflict';
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return parent::can_schedule( $args ) && ! empty( $this->connection->get_connection_data()['container_id'] );
	}

	/**
	 * Re-check the container. A failed check keeps the last result.
	 *
	 * @param array $items An array of job arguments.
	 */
	public function process_items( array $items ) {
		try {
			$this->connection->refresh_ads_conversion_conflict();
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
		}
	}

	/**
	 * Schedule the job, starting one interval from now since the container was just checked.
	 *
	 * @param array $args Job arguments.
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule( $args ) ) {
			$this->action_scheduler->schedule_recurring( time() + $this->get_interval(), $this->get_interval(), $this->get_process_item_hook(), $args );
		}
	}

	/**
	 * Stop the recurring checks.
	 */
	public function unschedule(): void {
		if ( ! $this->is_scheduled() ) {
			return;
		}

		try {
			$this->action_scheduler->cancel( $this->get_process_item_hook() );
		} catch ( ActionSchedulerException $e ) {
			// Already gone, e.g. it finished between the check and the cancel.
			return;
		}
	}

	/**
	 * The job is considered to be scheduled if the "process_item" action is currently pending or in-progress regardless of the arguments.
	 *
	 * @return bool
	 */
	public function is_scheduled(): bool {
		return $this->is_running( null );
	}
}
