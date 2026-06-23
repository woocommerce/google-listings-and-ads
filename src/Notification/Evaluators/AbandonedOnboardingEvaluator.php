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
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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
class AbandonedOnboardingEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, AdsAwareInterface, OptionsAwareInterface, Service {

	use AdsAwareTrait;
	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	/** @var MerchantAccountState */
	private $merchant_account_state;

	/** @var ServiceBasedMerchantState */
	private $service_based_merchant_state;

	/**
	 * AbandonedOnboardingEvaluator constructor.
	 *
	 * @param MerchantAccountState      $merchant_account_state
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 */
	public function __construct( MerchantAccountState $merchant_account_state, ServiceBasedMerchantState $service_based_merchant_state ) {
		$this->merchant_account_state       = $merchant_account_state;
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
		if ( ! $this->has_onboarding_step_started() ) {
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
	 * Determine whether at least one onboarding step has been started.
	 *
	 * @return bool
	 */
	private function has_onboarding_step_started(): bool {
		if ( boolval( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) ) ) {
			return true;
		}

		if ( boolval( $this->options->get( OptionsInterface::WP_TOS_ACCEPTED, false ) ) ) {
			return true;
		}

		if ( $this->options->get_merchant_id() > 0 ) {
			return true;
		}

		if ( $this->options->get_ads_id() > 0 ) {
			return true;
		}

		foreach ( $this->merchant_account_state->get( false ) as $step ) {
			if ( isset( $step['status'] ) && AccountState::STEP_DONE === $step['status'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether the merchant abandoned onboarding during account setup.
	 *
	 * @return bool
	 */
	private function is_abandoned_at_account_setup(): bool {
		if ( ! $this->merchant_center->is_google_connected() ) {
			return true;
		}

		if ( ! $this->ads_service->connected_account() ) {
			return true;
		}

		if ( $this->service_based_merchant_state->is_service_based_merchant() ) {
			return false;
		}

		return ! $this->is_merchant_center_connected();
	}

	/**
	 * Determine whether the Merchant Center account is connected.
	 *
	 * Mirrors the connected_account() check used by the mc/setup endpoint.
	 *
	 * @return bool
	 */
	private function is_merchant_center_connected(): bool {
		$merchant_id = $this->options->get_merchant_id();

		return $merchant_id > 0 && '' === $this->merchant_account_state->last_incomplete_step();
	}
}
