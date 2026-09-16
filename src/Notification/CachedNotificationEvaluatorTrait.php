<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Trait CachedNotificationEvaluatorTrait
 *
 * Caches an evaluator's boolean result to avoid repeated Google Ads API calls and database
 * queries on every admin page load. The cached condition is site-wide (no evaluator's
 * condition depends on the current user), so it is keyed per site by default: one transient
 * per notification, which a relevant action can invalidate with a single delete_transient()
 * even under an external object cache. Per-user concerns (dismiss, snooze, first-triggered
 * time) live in NotificationService state and are unaffected by this cache.
 *
 * The one-hour expiry is a fallback; evaluators with a discrete triggering action opt into
 * event-driven invalidation via InvalidatableNotificationEvaluatorInterface.
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
	 * Get the transient cache key. Site-scoped by default because the cached condition is
	 * store-wide; an evaluator whose condition genuinely varies per user can override this
	 * to NotificationCacheKeys::for_user().
	 *
	 * @return string
	 */
	protected function get_cache_key(): string {
		return NotificationCacheKeys::for_site( $this->get_id() );
	}
}
