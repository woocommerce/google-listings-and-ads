<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractNotificationJob
 * Generic class for the Notifications Jobs
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
abstract class AbstractNotificationJob extends AbstractActionSchedulerJob implements JobInterface {

	/**
	 * @var NotificationsService $notifications_service
	 */
	protected $notifications_service;

	/**
	 * @var MerchantCenterService $merchant_center
	 */
	protected $merchant_center;

	/**
	 * Notifications Jobs constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param NotificationsService      $notifications_service
	 * @param MerchantCenterService     $merchant_center
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		NotificationsService $notifications_service,
		MerchantCenterService $merchant_center
	) {
		$this->notifications_service = $notifications_service;
		$this->merchant_center       = $merchant_center;
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
	 * @throws NotificationJobException When MC is not ready.
	 */
	public function can_schedule( $args = [] ): bool {
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			do_action( 'woocommerce_gla_error', 'Cannot sync any products before setting up Google Merchant Center.', __METHOD__ );
			throw new NotificationJobException( __( 'Google Merchant Center has not been set up correctly. Please review your configuration.', 'google-listings-and-ads' ) );
		}

		/**
		 * Allow users to disable the notification job schedule.
		 *
		 * @since x.x.x
		 *
		 * @param bool $value The current filter value. By default, it is the result of `$this->can_schedule` function.
		 * @param string $job_name The current Job name.
		 * @param array $args The arguments for the schedule function with the item id and the topic.
		 */
		return apply_filters( 'woocommerce_gla_notification_job_can_schedule', $this->notifications_service->is_enabled() && parent::can_schedule( $args ), $this->get_job_name(), $args );
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
