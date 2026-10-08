<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchConsoleNotConnectedEvaluator
 *
 * Fires until the Search Console connection is fully configured and verified.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SearchConsoleNotConnectedEvaluator implements NotificationEvaluatorInterface, Service {

	/** @var Connection */
	private $connection;

	/**
	 * SearchConsoleNotConnectedEvaluator constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct( Connection $connection ) {
		$this->connection = $connection;
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
		$connection_data = $this->connection->get_connection_data();

		return Connection::STATE_CONNECTED !== ( $connection_data['state'] ?? null );
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
		return NotificationSnoozeDurations::SEARCH_CONSOLE_NOT_CONNECTED;
	}
}
