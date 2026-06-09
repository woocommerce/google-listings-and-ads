<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

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

defined( 'ABSPATH' ) || exit;

/**
 * Class AbandonedOnboardingEvaluator
 *
 * Fires when MC setup is not complete and at least one onboarding step has started.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class AbandonedOnboardingEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, OptionsAwareInterface, Service {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	/** @var MerchantAccountState */
	protected $merchant_account_state;

	/**
	 * AbandonedOnboardingEvaluator constructor.
	 *
	 * @param MerchantAccountState $merchant_account_state
	 */
	public function __construct( MerchantAccountState $merchant_account_state ) {
		$this->merchant_account_state = $merchant_account_state;
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
		if ( $this->merchant_center->is_setup_complete() ) {
			return false;
		}

		return $this->has_onboarding_step_started();
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
	protected function has_onboarding_step_started(): bool {
		if ( boolval( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) ) ) {
			return true;
		}

		if ( boolval( $this->options->get( OptionsInterface::WP_TOS_ACCEPTED, false ) ) ) {
			return true;
		}

		if ( $this->options->get_merchant_id() > 0 ) {
			return true;
		}

		foreach ( $this->merchant_account_state->get( false ) as $step ) {
			if ( isset( $step['status'] ) && AccountState::STEP_DONE === $step['status'] ) {
				return true;
			}
		}

		return false;
	}
}
