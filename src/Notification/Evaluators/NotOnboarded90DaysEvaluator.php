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

defined( 'ABSPATH' ) || exit;

/**
 * Class NotOnboarded90DaysEvaluator
 *
 * Fires when onboarding is not completed and WooCommerce was installed more than 90 days ago.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class NotOnboarded90DaysEvaluator implements NotificationEvaluatorInterface, OptionsAwareInterface, Service {

	use OptionsAwareTrait;

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

		$install_timestamp = $this->options->get( OptionsInterface::WC_INSTALL_TIMESTAMP );

		if ( ! $install_timestamp ) {
			return false;
		}

		return ( time() - (int) $install_timestamp ) > ( 90 * DAY_IN_SECONDS );
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
}
