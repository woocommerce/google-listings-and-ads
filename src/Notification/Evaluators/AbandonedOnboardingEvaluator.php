<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbandonedOnboardingEvaluator
 *
 * Fires when onboarding is incomplete and the merchant abandoned at Step 1 (Account Setup)
 * or Step 2 (Product Feed Configuration), using the same step tracking as the mc/setup endpoint.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class AbandonedOnboardingEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, AdsAwareInterface, Service {

	use AdsAwareTrait;
	use MerchantCenterAwareTrait;

	/** @var ServiceBasedMerchantState */
	private $service_based_merchant_state;

	/**
	 * AbandonedOnboardingEvaluator constructor.
	 *
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 */
	public function __construct( ServiceBasedMerchantState $service_based_merchant_state ) {
		$this->service_based_merchant_state = $service_based_merchant_state;
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

		$setup_status = $this->merchant_center->get_setup_status();

		if ( 'complete' === ( $setup_status['status'] ?? '' ) ) {
			return false;
		}

		$step = $setup_status['step'] ?? 'accounts';

		if ( ! in_array( $step, [ 'accounts', 'product_listings' ], true ) ) {
			return false;
		}

		if ( 'product_listings' === $step ) {
			return true;
		}

		return $this->is_abandoned_at_account_setup();
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

	/**
	 * Determine whether the merchant abandoned onboarding during account setup.
	 *
	 * @return bool
	 */
	private function is_abandoned_at_account_setup(): bool {
		if ( ! $this->ads_service->connected_account() ) {
			return true;
		}

		// Service-based merchants can't connect a Merchant Center account or enter
		// contact information, so those steps don't apply to them.
		if ( $this->service_based_merchant_state->is_service_based_merchant() ) {
			return false;
		}

		// Non-service-based merchants must connect their Merchant Center account and
		// enter their contact information to complete account setup.
		return ! $this->merchant_center->connected_account()
			|| ! $this->merchant_center->is_mc_contact_information_setup();
	}
}
