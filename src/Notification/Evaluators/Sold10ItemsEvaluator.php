<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\SiteScopedNotificationEvaluatorInterface;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sold10ItemsEvaluator
 *
 * Fires once the merchant has 10 or more paid WooCommerce orders with revenue.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class Sold10ItemsEvaluator implements SiteScopedNotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait;

	private const MINIMUM_ORDERS = 10;

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
		return $this->has_minimum_revenue_orders( self::MINIMUM_ORDERS );
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
	 * Site-scoped evaluators share one cache entry per store.
	 *
	 * @return string
	 */
	protected function get_cache_key(): string {
		return NotificationCacheKeys::for_site( $this->get_id() );
	}

	/**
	 * Whether the store has at least $minimum paid orders with revenue.
	 *
	 * Uses WooCommerce paid order statuses and excludes zero-total orders so
	 * only orders that generated revenue count toward the milestone.
	 *
	 * @param int $minimum
	 *
	 * @return bool
	 */
	protected function has_minimum_revenue_orders( int $minimum ): bool {
		$statuses = wc_get_is_paid_statuses();
		if ( empty( $statuses ) ) {
			return false;
		}

		$query_args = [
			'status' => $statuses,
			'limit'  => $minimum + 1,
			'return' => 'ids',
		];

		// The 'total' => [ 'value' => 0, 'operator' => '>' ] shorthand is only honoured
		// by the HPOS orders-table query. Under legacy post-meta storage it degrades to a
		// generic "IN" meta query and matches only zero-total orders, so branch explicitly.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$query_args['total'] = [
				'value'    => 0,
				'operator' => '>',
			];
		} else {
			$query_args['meta_query'] = [
				[
					'key'     => '_order_total',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				],
			];
		}

		$orders = wc_get_orders( $query_args );

		return count( $orders ) >= $minimum;
	}
}
