<?php
	declare( strict_types=1 );

	namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

	use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
	use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
	use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
	use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
	use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;


	defined( 'ABSPATH' ) || exit;

	/**
	 * Class AbstractNotificationJob
	 *
	 * Abstract class for all the Notifications Jobs
	 *
	 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
	 */
abstract class AbstractNotificationJob extends AbstractBatchedActionSchedulerJob implements JobInterface {

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
		return 'notifications/' . $this->get_topic();
	}

	/**
	 * Get the batch for the job
	 *
	 * @param int $batch_number
	 * @return array
	 */
	protected function get_batch( int $batch_number ): array {
		return $this->notifications_service->find_products( $this->get_required_status(), $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Get the batch size
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		return 1;
	}

	/**
	 * Logic when processing the items
	 *
	 * @param array $items
	 */
	protected function process_items( array $items ) {
		foreach ( $items as $item ) {
			$this->notifications_service->notify( $item, $this->get_topic() );
		}
	}

	/**
	 * Get the status to filter the products that should be processed on the job
	 *
	 * @return string
	 */
	abstract public function get_required_status(): string;

	/**
	 * Get the notification topic
	 *
	 * @return string
	 */
	abstract public function get_topic(): string;
}
