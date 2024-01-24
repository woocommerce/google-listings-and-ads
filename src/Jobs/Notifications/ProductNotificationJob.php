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
	 * Class ProductNotificationJob
	 *
	 * Class for all the Product Notifications Jobs
	 *
	 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
	 */
class ProductNotificationJob extends AbstractActionSchedulerJob implements JobInterface {

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
		 * Get the job name
		 *
		 * @return string
		 */
	public function get_name(): string {
		return 'notifications/products';
	}


		/**
		 * Logic when processing the items
		 *
		 * @param array $args Arguments with the item id and the topic
		 */
	protected function process_items( array $args ) {
		$item  = $args[0] ?? null;
		$topic = $args[1] ?? null;

		$item_id = $this->notifications_service->filter_product( $item, $topic );

		if ( ! is_null( $item_id ) && ! is_null( $topic ) ) {
			$this->notifications_service->notify( $item, $topic );
		}
	}

	/**
	 * Schedule the Product Notification Job
	 *
	 * @param array $args
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule( [ $args ] ) ) {
			$this->action_scheduler->schedule_immediate(
				$this->get_process_item_hook(),
				[ $args ]
			);
		}
	}
}
