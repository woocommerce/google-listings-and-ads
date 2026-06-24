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
	public const NOT_ONBOARDED_90_DAYS     = 40;
	public const ENHANCED_CONVERSIONS_OFF  = 50;
	public const TRACKING_OFF              = 60;
}
