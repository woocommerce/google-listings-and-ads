<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductDeleteNotificationJob
 *
 * Schedules a Job for triggering product Delete Notifications.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ProductDeleteNotificationJob extends AbstractNotificationJob {

	/**
	 * Get the available status for this Notification.
	 *
	 * @return string
	 */
	public function get_required_status(): string {
		return NotificationStatus::NOTIFICATION_PENDING_DELETE;
	}

	/**
	 * Get the Notification topic.
	 *
	 * @return string
	 */
	public function get_topic(): string {
		return NotificationsService::TOPIC_PRODUCT_DELETED;
	}
}
