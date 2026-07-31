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
		$order_ids      = wc_get_orders( $query );
		$youtube_orders = [];

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			$source = $order->get_meta( '_wc_order_attribution_utm_source' );

			// Check refund parent orders for youtube attribution meta.
			if ( $order instanceof WC_Order_Refund ) {
				$parent = wc_get_order( $order->get_parent_id() );
				$source = $parent->get_meta( '_wc_order_attribution_utm_source' );
			}

			if ( 'youtube' === $source ) {
				$youtube_orders[] = $order_id;
			}
		}

		return $youtube_orders;
	}
}
