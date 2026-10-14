<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Trait CachedNotificationEvaluatorTrait
 *
 * Stores an evaluator's boolean result so that Google Ads API calls and database queries never
 * run during an admin page load. should_show() only reads the stored result; the
 * RefreshNotificationEvaluators job calls refresh() in the background whenever needs_refresh()
 * reports the result as missing or stale. Classes using this trait implement
 * CachedNotificationEvaluatorInterface.
 *
 * The cached condition is site-wide (no evaluator's condition depends on the current user), so
 * it is keyed per site by default: one transient per notification. Per-user concerns (dismiss,
 * snooze, first-triggered time) live in NotificationService state and are unaffected by this cache.
 *
 * The stored result is kept for CACHE_EXPIRATION so a stale value can still be shown while the
 * refresh runs; it is recalculated once it is older than STALE_AFTER, or sooner when an
 * InvalidatableNotificationEvaluatorInterface hook marks it stale.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
trait CachedNotificationEvaluatorTrait {

	/**
	 * Whether the notification's condition is currently met, according to the stored result.
	 *
	 * Returns false when nothing is stored yet; the background refresh fills it in.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		$cached = get_transient( $this->get_cache_key() );

		if ( is_array( $cached ) ) {
			return ! empty( $cached['value'] );
		}

		// A plain 0/1 value stored before results carried a timestamp; still valid until refreshed.
		return (bool) $cached;
	}

	/**
	 * Whether the stored result is missing or stale and should be recalculated.
	 *
	 * @return bool
	 */
	public function needs_refresh(): bool {
		$cached = get_transient( $this->get_cache_key() );

		if ( ! is_array( $cached ) ) {
			return true;
		}

		$computed_at    = (int) ( $cached['computed_at'] ?? 0 );
		$invalidated_at = (int) ( $cached['invalidated_at'] ?? 0 );

		return $computed_at < time() - CachedNotificationEvaluatorInterface::STALE_AFTER || $invalidated_at > $computed_at;
	}

	/**
	 * Evaluate the condition now and store the result.
	 *
	 * @return bool The new result.
	 */
	public function refresh(): bool {
		$started_at = time();
		$result     = $this->evaluate_condition();

		// Keep an invalidation that landed mid-evaluation, so the stored result stays stale
		// and is recalculated again.
		$cached         = get_transient( $this->get_cache_key() );
		$invalidated_at = is_array( $cached ) ? (int) ( $cached['invalidated_at'] ?? 0 ) : 0;

		set_transient(
			$this->get_cache_key(),
			[
				'value'          => $result ? 1 : 0,
				'computed_at'    => $started_at,
				'invalidated_at' => $invalidated_at > $started_at ? $invalidated_at : 0,
			],
			CachedNotificationEvaluatorInterface::CACHE_EXPIRATION
		);

		return $result;
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	abstract protected function evaluate_condition(): bool;

	/**
	 * Get the transient cache key. Site-scoped because the cached condition is store-wide and
	 * is refreshed in the background, where there is no current user.
	 *
	 * @return string
	 */
	protected function get_cache_key(): string {
		return NotificationCacheKeys::for_site( $this->get_id() );
	}
}
