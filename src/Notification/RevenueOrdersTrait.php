<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification;

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Trait RevenueOrdersTrait
 *
 * Counts paid WooCommerce orders that generated revenue (a total greater than zero), across HPOS
 * and legacy stores. Paid statuses come from wc_get_is_paid_statuses(), so any custom status added
 * through the woocommerce_order_is_paid_statuses filter is included.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification
 */
trait RevenueOrdersTrait {

	/**
	 * Whether the store has at least $minimum paid orders with a total greater than zero.
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

		return $this->count_revenue_orders( $statuses, $minimum ) >= $minimum;
	}

	/**
	 * Count paid orders with a total greater than zero, up to $limit.
	 *
	 * A direct query is used because neither wc_get_orders() nor the HPOS orders-table query
	 * supports a numeric range comparison on the native total column: the
	 * ['value' => ..., 'operator' => '>'] shape is only honoured for meta queries, and on an
	 * orders-table store it silently degrades to "total_amount IN (0, 0)", the opposite of revenue.
	 *
	 * @param string[] $statuses Paid order statuses (without the "wc-" prefix).
	 * @param int      $limit    Maximum number of orders to count.
	 *
	 * @return int
	 */
	protected function count_revenue_orders( array $statuses, int $limit ): int {
		global $wpdb;

		$statuses     = array_map(
			static function ( string $status ): string {
				return 'wc-' . $status;
			},
			$statuses
		);
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$query = "SELECT id
				FROM {$wpdb->prefix}wc_orders
				WHERE type = 'shop_order'
					AND total_amount > 0
					AND status IN ( $placeholders )
				LIMIT %d";
		} else {
			$query = "SELECT posts.ID
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS meta
					ON posts.ID = meta.post_id
				WHERE posts.post_type = 'shop_order'
					AND posts.post_status IN ( $placeholders )
					AND meta.meta_key = '_order_total'
					AND meta.meta_value + 0 > 0
				LIMIT %d";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table names from $wpdb and built %s placeholders.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				$query,
				array_merge( $statuses, [ $limit ] )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return count( $ids );
	}
}
