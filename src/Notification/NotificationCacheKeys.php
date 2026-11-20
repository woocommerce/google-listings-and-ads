<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationCacheKeys
 *
 * Transient key helpers for notification evaluator result caching.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
class NotificationCacheKeys {

	public const TRANSIENT_PREFIX = 'gla_notif_';

	/**
	 * Cache key for a per-user evaluator result.
	 *
	 * @param string   $notification_id
	 * @param int|null $user_id         Defaults to the current user.
	 *
	 * @return string
	 */
	public static function for_user( string $notification_id, ?int $user_id = null ): string {
		return self::TRANSIENT_PREFIX . $notification_id . '_' . ( $user_id ?? get_current_user_id() );
	}

	/**
	 * Cache key for a site-scoped evaluator result.
	 *
	 * @param string $notification_id
	 *
	 * @return string
	 */
	public static function for_site( string $notification_id ): string {
		return self::TRANSIENT_PREFIX . $notification_id;
	}
}
