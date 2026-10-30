<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ResettableNotificationEvaluatorInterface
 *
 * Opt-in for evaluators whose notification should show again, even after being dismissed, once
 * its condition has cleared and later returns. An implementing evaluator declares the action hooks
 * that mean its condition has cleared; NotificationService binds each hook and resets the
 * notification's stored state when the hook fires.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
interface ResettableNotificationEvaluatorInterface extends NotificationEvaluatorInterface {

	/**
	 * Action hook names that should reset this notification's stored state.
	 *
	 * @return string[]
	 */
	public function get_reset_hooks(): array;
}
