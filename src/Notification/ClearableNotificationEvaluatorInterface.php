<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ClearableNotificationEvaluatorInterface
 *
 * Opt-in for evaluators that keep their own cached data outside the CachedNotificationEvaluatorTrait
 * result, such as an Ads report read. NotificationService::clear_cached_results() calls
 * clear_cached_result() when a Google account is disconnected, so the next evaluation starts
 * from current data.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
interface ClearableNotificationEvaluatorInterface extends NotificationEvaluatorInterface {

	/**
	 * Delete the evaluator's cached data.
	 *
	 * @return void
	 */
	public function clear_cached_result(): void;
}
