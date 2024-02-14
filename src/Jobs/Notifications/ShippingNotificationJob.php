<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingNotificationJob
 * Class for the Shipping Notifications
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ShippingNotificationJob extends AbstractActionSchedulerJob implements JobInterface {

	/**
	 * @var NotificationsService $notifications_service
	 */
	protected $notifications_service;

	/**
	 * @var string $topic
	 */
	protected $topic;

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
		$this->topic                 = NotificationsService::TOPIC_SHIPPING_UPDATED;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get the job name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'notifications/shipping';
	}


	/**
	 * Logic when processing the items
	 *
	 * @param array $args Arguments with the item id and the topic
	 */
	protected function process_items( array $args ): void {
		if ( ! isset( $args[1] ) ) {
			return;
		}

		$item  = $args[0];
		$topic = $args[1];
		$this->notifications_service->notify( $topic, $item );
	}

	/**
	 * Schedule the Product Notification Job
	 *
	 * @param array $args
	 */
	public function schedule( array $args = [] ): void {
		if ( $this->can_schedule( $args ) ) {
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
		 * @since x.x.x
		 *
		 * @param bool $value The current filter value. By default, it is the result of `$this->can_schedule` function.
		 * @param array $args The arguments for the schedule function with the item id and the topic.
		 */
		return apply_filters( 'woocommerce_gla_shipping_notification_job_can_schedule', $this->notifications_service->is_enabled() && parent::can_schedule( $args ), $args );
	}
}
