<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Marker interface for notifications whose trigger/dismiss state is stored
 * site-wide (once per merchant) rather than per admin user.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
interface SiteScopedNotificationEvaluatorInterface extends NotificationEvaluatorInterface {}
