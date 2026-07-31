<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Interface NotificationEvaluatorInterface
 *
 * Implemented by classes that evaluate whether a single notification should be
 * shown. Evaluators are resolved as a tagged collection via the container, so
 * each implementation is registered with its interface tag.
 *
 * Caching is the responsibility of each evaluator. Evaluators for notifications
 * that must re-evaluate on every page load should bypass caching.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
interface NotificationEvaluatorInterface {

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool;

	/**
	 * Get the notification's priority. Lower values are returned first.
	 *
	 * @return int
	 */
	public function get_priority(): int;

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * Return NotificationSnoozeDurations::UNTIL_NEXT_LOGIN when dismissal resets on the next login.
	 * Return null when dismissal is permanent.
	 * Return a positive integer for time-based snooze durations in seconds.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int;
}
