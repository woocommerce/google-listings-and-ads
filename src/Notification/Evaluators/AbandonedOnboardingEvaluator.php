<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbandonedOnboardingEvaluator
 *
 * Fires when onboarding is incomplete and the merchant abandoned at Step 1 (Account Setup)
 * or Step 2 (Product Feed Configuration), using the same step tracking as the mc/setup endpoint.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class AbandonedOnboardingEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, Service {

	use MerchantCenterAwareTrait;

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'abandoned-onboarding';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		// Google account connection is the first intentional GLA onboarding action.
		if ( ! $this->merchant_center->is_google_connected() ) {
			return false;
		}

		$setup_status = $this->merchant_center->get_setup_status();

		if ( 'complete' === ( $setup_status['status'] ?? '' ) ) {
			return false;
		}

		$step = $setup_status['step'] ?? '';

		// Only an incomplete account setup (Step 1) or product feed configuration (Step 2)
		// counts as abandoned. The setup status already derives the step from the account
		// connection state, so there is no need to re-check the individual accounts here.
		return in_array( $step, [ 'accounts', 'product_listings' ], true );
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::ABANDONED_ONBOARDING;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::ABANDONED_ONBOARDING;
	}
}
