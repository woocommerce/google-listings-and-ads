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

	/**
	 * Dismissal resets when the merchant logs in again.
	 */
	public const UNTIL_NEXT_LOGIN = -1;

	public const ABANDONED_ONBOARDING      = 30 * DAY_IN_SECONDS;
	public const NOT_ONBOARDED             = 30 * DAY_IN_SECONDS;
	public const ENHANCED_CONVERSIONS_OFF  = 7 * DAY_IN_SECONDS;
	public const TRACKING_OFF              = 7 * DAY_IN_SECONDS;
	public const PAID_ORDERS               = 7 * DAY_IN_SECONDS;
	public const READY_BUT_NO_SALES        = 7 * DAY_IN_SECONDS;
	public const COUPONS_NOT_SYNCED        = 7 * DAY_IN_SECONDS;
	public const SALES_NOT_GROWING         = 7 * DAY_IN_SECONDS;
	public const PAUSED_CAMPAIGN           = 7 * DAY_IN_SECONDS;
	public const CAMPAIGN_NO_SALES         = 7 * DAY_IN_SECONDS;
	public const RECOMMENDATIONS_AVAILABLE = 7 * DAY_IN_SECONDS;
}
