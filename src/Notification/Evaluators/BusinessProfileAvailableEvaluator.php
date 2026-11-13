<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\Connection as BusinessProfileConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileAvailableEvaluator
 *
 * Fires when onboarding is complete and no Google Business Profile location is connected.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class BusinessProfileAvailableEvaluator implements NotificationEvaluatorInterface, Service {

	/** @var BusinessProfileConnection */
	private $business_profile_connection;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * BusinessProfileAvailableEvaluator constructor.
	 *
	 * @param BusinessProfileConnection $business_profile_connection
	 * @param OnboardingCompleted       $onboarding_completed
	 */
	public function __construct( BusinessProfileConnection $business_profile_connection, OnboardingCompleted $onboarding_completed ) {
		$this->business_profile_connection = $business_profile_connection;
		$this->onboarding_completed        = $onboarding_completed;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'business-profile-available';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * The connection status is read from the stored location, so this never calls Google.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		$status = $this->business_profile_connection->get_status();

		return BusinessProfileConnection::STATUS_CONNECTED !== ( $status['status'] ?? null );
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::BUSINESS_PROFILE_AVAILABLE;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return null;
	}
}
