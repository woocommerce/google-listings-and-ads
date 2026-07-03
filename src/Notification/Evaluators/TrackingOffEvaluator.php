<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use WC_Site_Tracking;

defined( 'ABSPATH' ) || exit;

/**
 * Class TrackingOffEvaluator
 *
 * Fires when WooCommerce usage tracking is disabled.
 *
 * Uses the same source of truth as the client-side isWCTracksEnabled() helper
 * (WC_Site_Tracking::is_tracking_enabled(), which is what populates
 * window.wcTracks.isEnabled), so the signal stays in sync with how the rest of
 * the plugin reads the tracking opt-in — including the tracking filters, not just
 * the raw woocommerce_allow_tracking option.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class TrackingOffEvaluator implements NotificationEvaluatorInterface, Service {

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'tracking-off';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		return ! $this->is_wc_tracking_enabled();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::TRACKING_OFF;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::TRACKING_OFF;
	}

	/**
	 * Whether WooCommerce usage tracking is enabled.
	 *
	 * Mirrors the client-side isWCTracksEnabled() helper by reading the same value
	 * WooCommerce uses for window.wcTracks.isEnabled. When the tracking subsystem is
	 * unavailable, tracking is treated as disabled (matching that helper).
	 *
	 * @return bool
	 */
	private function is_wc_tracking_enabled(): bool {
		if ( ! class_exists( WC_Site_Tracking::class ) ) {
			return false;
		}

		return WC_Site_Tracking::is_tracking_enabled();
	}
}
