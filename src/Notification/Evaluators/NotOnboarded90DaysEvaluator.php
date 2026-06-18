<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotOnboarded90DaysEvaluator
 *
 * Fires when the merchant has not connected a Google account and either WooCommerce
 * or the plugin has been active for 90 or more days.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class NotOnboarded90DaysEvaluator implements NotificationEvaluatorInterface, OptionsAwareInterface, Service {

	use OptionsAwareTrait;

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
		if ( boolval( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) ) ) {
			return false;
		}

		$reference_timestamp = $this->get_reference_timestamp();

		if ( ! $reference_timestamp ) {
			return false;
		}

		return ( time() - $reference_timestamp ) >= ( 90 * DAY_IN_SECONDS );
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
	 * Get the earliest available install timestamp for eligibility checks.
	 *
	 * Uses the older of the WooCommerce and plugin install timestamps so merchants
	 * who had Woo before installing G4W are evaluated from their Woo install date.
	 *
	 * @return int|null
	 */
	protected function get_reference_timestamp(): ?int {
		$wc_timestamp     = $this->options->get( OptionsInterface::WC_INSTALL_TIMESTAMP );
		$plugin_timestamp = $this->options->get( OptionsInterface::INSTALL_TIMESTAMP );

		$timestamps = array_filter(
			[
				is_numeric( $wc_timestamp ) ? (int) $wc_timestamp : null,
				is_numeric( $plugin_timestamp ) ? (int) $plugin_timestamp : null,
			]
		);

		if ( empty( $timestamps ) ) {
			return null;
		}

		return min( $timestamps );
	}
}
