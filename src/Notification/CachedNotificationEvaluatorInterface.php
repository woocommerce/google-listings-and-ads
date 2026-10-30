<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Interface CachedNotificationEvaluatorInterface
 *
 * An evaluator whose condition is expensive to check (Google Ads API calls, large order or
 * coupon queries). should_show() only reads the stored result, and the condition is evaluated
 * in the background by the RefreshNotificationEvaluators job, so no admin page load waits for it.
 *
 * The refresh runs without a current user, so an implementing evaluator's condition must not
 * depend on who is viewing the page.
 *
 * @since n.e.x.t
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
interface CachedNotificationEvaluatorInterface extends NotificationEvaluatorInterface {

	/**
	 * Age in seconds after which a stored result is recalculated.
	 */
	public const STALE_AFTER = HOUR_IN_SECONDS;

	/**
	 * How long a stored result is kept, so a stale value can still be shown while it is recalculated.
	 */
	public const CACHE_EXPIRATION = DAY_IN_SECONDS;

	/**
	 * Whether the stored result is missing or stale and should be recalculated.
	 *
	 * @return bool
	 */
	public function needs_refresh(): bool;

	/**
	 * Evaluate the condition now and store the result.
	 *
	 * @return bool The new result.
	 */
	public function refresh(): bool;

	/**
	 * Mark the stored result stale, so the next refresh recalculates it. The result stays
	 * visible until then.
	 *
	 * @return void
	 */
	public function invalidate(): void;
}
