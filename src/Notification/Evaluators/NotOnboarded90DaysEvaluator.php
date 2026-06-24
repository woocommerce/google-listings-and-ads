<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WPAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WPAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotOnboarded90DaysEvaluator
 *
 * Fires when Google for WooCommerce onboarding is not complete, WooCommerce onboarding
 * has been completed or skipped, and WooCommerce has been installed for 90+ days.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class NotOnboarded90DaysEvaluator implements NotificationEvaluatorInterface, OptionsAwareInterface, WPAwareInterface, Service {

	use OptionsAwareTrait;
	use WPAwareTrait;

	/**
	 * WooCommerce option that stores onboarding wizard progress.
	 */
	private const WC_ONBOARDING_PROFILE_OPTION = 'woocommerce_onboarding_profile';

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * NotOnboarded90DaysEvaluator constructor.
	 *
	 * @param OnboardingCompleted $onboarding_completed
	 */
	public function __construct( OnboardingCompleted $onboarding_completed ) {
		$this->onboarding_completed = $onboarding_completed;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'not-onboarded-90-days';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		if ( ! $this->has_completed_or_skipped_wc_onboarding() ) {
			return false;
		}

		$install_timestamp = $this->options->get( OptionsInterface::WC_INSTALL_TIMESTAMP );

		if ( ! $install_timestamp ) {
			return false;
		}

		return ( time() - (int) $install_timestamp ) >= ( 90 * DAY_IN_SECONDS );
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::NOT_ONBOARDED_90_DAYS;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::NOT_ONBOARDED_90_DAYS;
	}

	/**
	 * Whether the merchant has completed or skipped WooCommerce onboarding.
	 *
	 * @return bool
	 */
	protected function has_completed_or_skipped_wc_onboarding(): bool {
		$profile = $this->wp->get_option( self::WC_ONBOARDING_PROFILE_OPTION, [] );

		if ( ! is_array( $profile ) ) {
			return false;
		}

		if ( ! empty( $profile['completed'] ) ) {
			return true;
		}

		return ! empty( $profile['skipped'] );
	}
}
