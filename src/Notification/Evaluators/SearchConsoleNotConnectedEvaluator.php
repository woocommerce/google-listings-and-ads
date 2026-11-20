<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchConsoleNotConnectedEvaluator
 *
 * Fires once setup is finished, until the Search Console connection is fully configured and verified.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SearchConsoleNotConnectedEvaluator implements NotificationEvaluatorInterface, Service {

	/** @var Connection */
	private $connection;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * SearchConsoleNotConnectedEvaluator constructor.
	 *
	 * @param Connection          $connection
	 * @param OnboardingCompleted $onboarding_completed
	 */
	public function __construct( Connection $connection, OnboardingCompleted $onboarding_completed ) {
		$this->connection           = $connection;
		$this->onboarding_completed = $onboarding_completed;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'search-console-not-connected';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		// A store that hasn't finished setup can't open Reports, so it couldn't see the report this prompt leads to.
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		return ! $this->connection->is_connected_from_stored_data();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::SEARCH_CONSOLE_NOT_CONNECTED;
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
