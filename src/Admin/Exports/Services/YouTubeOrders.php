<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Order_Refund;

defined( 'ABSPATH' ) || exit;

/**
 * Class YouTubeOrders
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Contracts
 */
class YouTubeOrders implements Service {
	/**
	 * Return an array of WooCommerce order IDs for the Merchant Conversions Report CSV.
	 *
	 * @param string  $date
	 * @param integer $limit
	 * @param integer $offset
	 * @return array
	 */
	public function find_orders( string $date = '', int $limit = -1, int $offset = 0 ): array {
		// Use yesterdays date if no date passed.
		$date = empty( $date ) ? gmdate( 'Y-m-d', strtotime( '-1 day' ) ) : $date;

		$query = [
			'date_created' => $date,
			'limit'        => $limit,
			'offset'       => $offset,
			'return'       => 'ids',
			'type'         => [ 'shop_order', 'shop_order_refund' ],
		];

		// Get all orders and refunds for the specific day.
		// Meta filtering will be done in find_orders() to leverage meta caching.
		$order_ids = wc_get_orders( $query );

		// Filter orders and refunds by YouTube attribution meta.
		$filtered_order_ids = [];
		$refund_parent_ids  = [];

		// First pass: process refunds to collect parent order IDs
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			if ( $order instanceof WC_Order_Refund ) {
				try {
					$parent_id = $order->get_parent_id();
					if ( ! $parent_id ) {
						continue;
					}
					$parent_order = wc_get_order( $parent_id );
					if ( ! $parent_order ) {
						continue;
					}
					if ( 'youtube' === $parent_order->get_meta( '_wc_order_attribution_utm_source' ) ) {
						$filtered_order_ids[] = $order_id;
						$refund_parent_ids[]  = $parent_id;
					}
				} catch ( \Exception $e ) {
					// Skip refunds with invalid parent IDs.
					continue;
				}
			}
		}

		// Second pass: process regular orders, excluding parent orders that have refunds on the same date
		foreach ( $order_ids as $order_id ) {
			// Skip if this order is a parent of a refund on the same date
			if ( in_array( $order_id, $refund_parent_ids, true ) ) {
				continue;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			if ( ! ( $order instanceof WC_Order_Refund ) ) {
				$utm_source = $order->get_meta( '_wc_order_attribution_utm_source' );
				if ( 'youtube' === $utm_source ) {
					$filtered_order_ids[] = $order_id;
				}
			}
		}

		return array_unique( $filtered_order_ids );
	}

}
