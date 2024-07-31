<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractNotificationJob
 * Generic class for the Notifications Jobs
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
abstract class AbstractNotificationJob extends AbstractActionSchedulerJob implements JobInterface {

	/**
	 * @var NotificationsService $notifications_service
	 */
	protected $notifications_service;

	/**
	 * Notifications Jobs constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param NotificationsService      $notifications_service
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		NotificationsService $notifications_service
	) {
		$this->notifications_service = $notifications_service;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the parent job name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'notifications/' . $this->get_job_name();
	}


	/**
	 * Schedule the Job
	 *
	 * @param array $args
	 */
	public function schedule( array $args = [] ): void {
		if ( $this->can_schedule( [ $args ] ) ) {
			$this->action_scheduler->schedule_immediate(
				$this->get_process_item_hook(),
				[ $args ]
			);
		}
	}


	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		/**
		 * Allow users to disable the notification job schedule.
		 *
		 * @since 2.8.0
		 *
		 * @param bool $value The current filter value. By default, it is the result of `$this->can_schedule` function.
		 * @param string $job_name The current Job name.
		 * @param array $args The arguments for the schedule function with the item id and the topic.
		 */
		return apply_filters( 'woocommerce_gla_notification_job_can_schedule', $this->notifications_service->is_ready() && parent::can_schedule( $args ), $this->get_job_name(), $args );
	}

	/**
	 * Get the child job name
	 *
	 * @return string
	 */
	abstract public function get_job_name(): string;

	/**
	 * Logic when processing the items
	 *
	 * @param array $args
	 */
	abstract protected function process_items( array $args ): void;
}
