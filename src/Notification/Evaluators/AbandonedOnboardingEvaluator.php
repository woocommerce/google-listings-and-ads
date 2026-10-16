<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbandonedOnboardingEvaluator
 *
 * Fires when onboarding is incomplete and the merchant abandoned at Step 1 (Account Setup)
 * or Step 2 (Product Feed Configuration), using the same step tracking as the mc/setup endpoint.
 *
 * Service-based (ads-only) merchants never complete Merchant Center setup, so the Merchant
 * Center–based setup status is not a reliable "abandoned onboarding" signal for them. For
 * those merchants the Merchant Center check is excluded and the onboarding-complete flag is
 * used instead: a service-based merchant who finished the ads-only onboarding is done, while
 * one who has not is still genuinely mid-onboarding.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class AbandonedOnboardingEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, Service {

	use MerchantCenterAwareTrait;

	/** @var ServiceBasedMerchantState */
	private $service_based_merchant_state;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * AbandonedOnboardingEvaluator constructor.
	 *
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 * @param OnboardingCompleted       $onboarding_completed
	 */
	public function __construct( ServiceBasedMerchantState $service_based_merchant_state, OnboardingCompleted $onboarding_completed ) {
		$this->service_based_merchant_state = $service_based_merchant_state;
		$this->onboarding_completed         = $onboarding_completed;
	}

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

		// Service-based (ads-only) merchants never complete Merchant Center setup, so the
		// Merchant Center setup status would keep this notification firing indefinitely.
		// Exclude the Merchant Center check for them and rely on the onboarding-complete
		// flag: a service-based merchant who finished the ads-only onboarding is done,
		// while one who has not is still genuinely mid-onboarding.
		if ( $this->service_based_merchant_state->is_service_based_merchant() ) {
			return ! $this->onboarding_completed->is_onboarding_complete();
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
