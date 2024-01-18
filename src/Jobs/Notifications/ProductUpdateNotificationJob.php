<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductUpdateNotificationJob
 *
 * Schedules a Job for triggering product Update Notifications.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ProductUpdateNotificationJob extends AbstractNotificationJob {

	/**
	 * Get the available status for this Notification.
	 *
	 * @return string
	 */
	public function get_required_status(): string {
		return NotificationStatus::NOTIFICATION_PENDING_UPDATE;
	}

	/**
	 * Get the Notification topic.
	 *
	 * @return string
	 */
	public function get_topic(): string {
		return NotificationsService::TOPIC_PRODUCT_UPDATED;
	}
}
