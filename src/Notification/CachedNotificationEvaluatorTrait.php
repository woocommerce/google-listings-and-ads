<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Trait CachedNotificationEvaluatorTrait
 *
 * Caches evaluator results for one hour to avoid repeated database queries. Scope (user or
 * site) is determined by get_cache_key().
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
trait CachedNotificationEvaluatorTrait {

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		$cached = get_transient( $this->get_cache_key() );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		$result = $this->evaluate_condition();
		set_transient( $this->get_cache_key(), $result ? 1 : 0, HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	abstract protected function evaluate_condition(): bool;

	/**
	 * Get the transient cache key for the current user.
	 *
	 * @return string
	 */
	protected function get_cache_key(): string {
		return NotificationCacheKeys::for_user( $this->get_id() );
	}
}
