<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Interface InvalidatableNotificationEvaluatorInterface
 *
 * Opt-in for cached evaluators whose result should be recalculated as soon as a relevant
 * action happens, rather than only when the cached result goes stale after an hour. An
 * implementing evaluator declares the action hooks that make its condition change;
 * NotificationCacheInvalidator binds each hook and, when one fires, marks the evaluator's cached
 * result stale and schedules a background refresh.
 *
 * Evaluators without a discrete triggering action (gradual sales/accumulation trends) do not
 * implement this and rely solely on the result going stale.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
interface InvalidatableNotificationEvaluatorInterface extends NotificationEvaluatorInterface {

	/**
	 * Action hook names that should invalidate this evaluator's cached result.
	 *
	 * @return string[]
	 */
	public function get_invalidation_hooks(): array;
}
