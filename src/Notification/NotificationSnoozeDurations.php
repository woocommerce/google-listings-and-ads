<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationSnoozeDurations
 *
 * Snooze durations in seconds for temporarily dismissible notifications.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
class NotificationSnoozeDurations {

	public const ABANDONED_ONBOARDING     = 30 * DAY_IN_SECONDS;
	public const NOT_ONBOARDED_90_DAYS    = 30 * DAY_IN_SECONDS;
	public const ENHANCED_CONVERSIONS_OFF = 7 * DAY_IN_SECONDS;
	public const TRACKING_OFF             = 7 * DAY_IN_SECONDS;
}
