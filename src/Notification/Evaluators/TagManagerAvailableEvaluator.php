<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerAvailableEvaluator
 *
 * Fires when onboarding is complete and Google Tag Manager is not fully connected.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class TagManagerAvailableEvaluator implements NotificationEvaluatorInterface, Service {

	/** @var TagManagerConnection */
	private $tag_manager_connection;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * TagManagerAvailableEvaluator constructor.
	 *
	 * @param TagManagerConnection $tag_manager_connection
	 * @param OnboardingCompleted  $onboarding_completed
	 */
	public function __construct( TagManagerConnection $tag_manager_connection, OnboardingCompleted $onboarding_completed ) {
		$this->tag_manager_connection = $tag_manager_connection;
		$this->onboarding_completed   = $onboarding_completed;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'tag-manager-available';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * Reads the stored connection rather than the connection status, which
	 * makes a remote request on every call.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		$connection = $this->tag_manager_connection->get_connection_data();

		return empty( $connection['account_id'] ) || empty( $connection['container_id'] );
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::TAG_MANAGER_AVAILABLE;
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
