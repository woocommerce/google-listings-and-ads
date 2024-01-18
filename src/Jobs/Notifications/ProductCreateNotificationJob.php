<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductCreateNotificationJob
 *
 * Schedules a Job for triggering product Create Notifications.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ProductCreateNotificationJob extends AbstractNotificationJob {

	/**
	 * Get the available status for this Notification.
	 *
	 * @return string
	 */
	public function get_required_status(): string {
		return NotificationStatus::NOTIFICATION_PENDING_CREATE;
	}

	/**
	 * Get the Notification topic.
	 *
	 * @return string
	 */
	public function get_topic(): string {
		return NotificationsService::TOPIC_PRODUCT_CREATED;
	}
}
