<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationPriorities
 *
 * Priority values for notification evaluators. Lower values are returned first.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
class NotificationPriorities {

	public const PRODUCT_ISSUES            = 10;
	public const SKIPPED_CAMPAIGN_CREATION = 20;
	public const ABANDONED_ONBOARDING      = 30;
	public const NOT_ONBOARDED             = 40;
	public const ENHANCED_CONVERSIONS_OFF  = 50;
	public const TRACKING_OFF              = 60;
	public const PAID_ORDERS               = 70;
	public const READY_BUT_NO_SALES        = 80;
	public const COUPONS_NOT_SYNCED        = 90;
	public const SALES_NOT_GROWING         = 100;
	public const PAUSED_CAMPAIGN           = 110;
	public const CAMPAIGN_NO_SALES         = 120;
	public const RECOMMENDATIONS_AVAILABLE = 130;
}
