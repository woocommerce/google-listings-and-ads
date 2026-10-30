<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;

defined( 'ABSPATH' ) || exit;

/**
 * Class RefreshNotificationEvaluators
 *
 * Recalculates stale cached notification results outside the request that found them stale,
 * so Google Ads API calls and large queries never run during an admin page load.
 *
 * @since n.e.x.t
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class RefreshNotificationEvaluators extends AbstractActionSchedulerJob {

	/**
	 * @var NotificationService
	 */
	protected $notification_service;

	/**
	 * RefreshNotificationEvaluators constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param NotificationService       $notification_service
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, NotificationService $notification_service ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->notification_service = $notification_service;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'refresh_notification_evaluators';
	}

	/**
	 * Schedule the job, unless a refresh is already pending or running.
	 *
	 * @param array $args Unused.
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule() ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook() );
		}
	}

	/**
	 * Recalculate the stale cached notification results.
	 *
	 * @param array $items Unused.
	 */
	protected function process_items( array $items ) {
		$this->notification_service->refresh_stale_evaluators();
	}
}
