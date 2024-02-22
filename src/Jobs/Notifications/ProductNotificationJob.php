<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;


defined( 'ABSPATH' ) || exit;

/**
 * Class ProductNotificationJob
 * Class for all the Product Notifications Jobs
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ProductNotificationJob extends AbstractNotificationJob {

	/**
	 * @var NotificationsService $notifications_service
	 */
	protected $notifications_service;

	/**
	 * @var ProductHelper $product_helper
	 */
	protected $product_helper;

	/**
	 * Notifications Jobs constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param NotificationsService      $notifications_service
	 * @param ProductHelper             $product_helper
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		NotificationsService $notifications_service,
		ProductHelper $product_helper
	) {
		$this->notifications_service = $notifications_service;
		$this->product_helper        = $product_helper;
		parent::__construct( $action_scheduler, $monitor, $notifications_service );
	}

	/**
	 * Get the job name
	 *
	 * @return string
	 */
	public function get_job_name(): string {
		return 'products';
	}


	/**
	 * Logic when processing the items
	 *
	 * @param array $args Arguments with the item id and the topic
	 */
	protected function process_items( array $args ): void {
		if ( ! isset( $args[0] ) || ! isset( $args[1] ) ) {
			do_action(
				'woocommerce_gla_error',
				'Error processing Product Notification Job. Item ID and Topic are mandatory.',
				__METHOD__
			);
			return;
		}

		$item  = $args[0];
		$topic = $args[1];

		if ( $this->can_process( $item, $topic ) && $this->notifications_service->notify( $topic, $item ) ) {
			$this->set_status( $item, $this->get_after_notification_status( $topic ) );
		}
	}

	/**
	 * Set the notification status for a product.
	 *
	 * @param int    $product_id
	 * @param string $status
	 */
	protected function set_status( int $product_id, string $status ): void {
		$product = $this->product_helper->get_wc_product( $product_id );
		$this->product_helper->set_notification_status( $product, $status );
	}

	/**
	 * Get the Notification Status after the notification happens
	 *
	 * @param string $topic
	 * @return string
	 */
	protected function get_after_notification_status( string $topic ): string {
		if ( str_contains( $topic, '.create' ) ) {
			return NotificationStatus::NOTIFICATION_CREATED;
		} elseif ( str_contains( $topic, '.delete' ) ) {
			return NotificationStatus::NOTIFICATION_DELETED;
		} else {
			return NotificationStatus::NOTIFICATION_UPDATED;
		}
	}

	/**
	 * Checks if the item can be processed based on the topic.
	 * This is needed because the product can change the Notification Status before
	 * the Job process the item.
	 *
	 * @param int    $product_id
	 * @param string $topic
	 * @return bool
	 */
	protected function can_process( int $product_id, string $topic ): bool {
		$product = $this->product_helper->get_wc_product( $product_id );
		if ( str_contains( $topic, '.create' ) ) {
			return $this->product_helper->should_trigger_create_notification( $product );
		} elseif ( str_contains( $topic, '.delete' ) ) {
			return $this->product_helper->should_trigger_delete_notification( $product );
		} else {
			return $this->product_helper->should_trigger_update_notification( $product );
		}
	}
}
