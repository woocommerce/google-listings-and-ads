<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sold10ItemsEvaluator
 *
 * Fires once the merchant has 10 or more completed orders.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class Sold10ItemsEvaluator implements NotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait;

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'sold-10-items';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		return $this->get_completed_order_count() >= 10;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::SOLD_10_ITEMS;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::SOLD_10_ITEMS;
	}

	/**
	 * Count completed orders up to the query limit.
	 *
	 * @return int
	 */
	protected function get_completed_order_count(): int {
		$orders = wc_get_orders(
			[
				'status' => 'completed',
				'limit'  => 11,
				'return' => 'ids',
			]
		);

		return count( $orders );
	}
}
