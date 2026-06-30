<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
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
		return 'gla_notif_' . $this->get_id();
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
		global $wpdb;

		$statuses = $this->get_paid_order_statuses();
		if ( empty( $statuses ) ) {
			return false;
		}

		$wc_statuses         = array_map(
			static function ( string $status ): string {
				return 'wc-' . $status;
			},
			$statuses
		);
		$status_placeholders = implode( ', ', array_fill( 0, count( $wc_statuses ), '%s' ) );
		$query_limit         = $minimum + 1;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$query = "SELECT COUNT(*) FROM (
				SELECT id
				FROM {$wpdb->prefix}wc_orders
				WHERE type = 'shop_order'
					AND status IN ( {$status_placeholders} )
					AND total_amount > 0
				LIMIT %d
			) AS revenue_orders";
		} else {
			$query = "SELECT COUNT(*) FROM (
				SELECT posts.ID
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS meta
					ON posts.ID = meta.post_id
				WHERE posts.post_type = 'shop_order'
					AND posts.post_status IN ( {$status_placeholders} )
					AND meta.meta_key = '_order_total'
					AND meta.meta_value + 0 > 0
				LIMIT %d
			) AS revenue_orders";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- dynamic IN() placeholders.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				$query,
				array_merge( $wc_statuses, [ $query_limit ] )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return (int) $count >= $minimum;
	}

	/**
	 * Returns WooCommerce order statuses that represent paid orders.
	 *
	 * @return string[]
	 */
	private function get_paid_order_statuses(): array {
		// compatibility-code "WC < 3.0" -- wc_get_is_paid_statuses() added in 3.0
		if ( function_exists( 'wc_get_is_paid_statuses' ) ) {
			return wc_get_is_paid_statuses();
		}

		return [ 'processing', 'completed' ];
	}
}
