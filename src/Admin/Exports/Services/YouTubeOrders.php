<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

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

		// Fetch all orders and refunds for the date without meta filtering.
		// We'll filter by meta during the loop to leverage meta caching.
		$purchase_ids = $this->find_purchases( $date, $limit, $offset );
		$refund_ids   = $this->find_refunds( $date, $limit, $offset );

		// Filter orders and refunds by YouTube attribution meta.
		$filtered_order_ids = [];

		// Filter purchases
		foreach ( $purchase_ids as $purchase_id ) {
			$order = wc_get_order( $purchase_id );
			if ( $order && 'youtube' === $order->get_meta( '_wc_order_attribution_utm_source' ) ) {
				$filtered_order_ids[] = $purchase_id;
			}
		}

		// Filter refunds
		foreach ( $refund_ids as $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( $refund && 'shop_order_refund' === $refund->get_type() ) {
				$parent_order = wc_get_order( $refund->get_parent_id() );
				if ( $parent_order && 'youtube' === $parent_order->get_meta( '_wc_order_attribution_utm_source' ) ) {
					$filtered_order_ids[] = $refund_id;
				}
			}
		}

		return array_unique( $filtered_order_ids );
	}

	/**
	 * Return an array of WooCommerce purchase order IDs for the Merchant Conversions Report CSV.
	 *
	 * @param string  $date
	 * @param integer $limit
	 * @param integer $offset
	 * @return array
	 */
	private function find_purchases( string $date = '', int $limit = -1, int $offset = 0 ): array {
		// Use yesterdays date if no date passed.
		$date = empty( $date ) ? gmdate( 'Y-m-d', strtotime( '-1 day' ) ) : $date;

		$query = [
			'date_created' => $date,
			'limit'        => $limit,
			'offset'       => $offset,
			'return'       => 'ids',
			'type'         => 'shop_order',
		];

		return wc_get_orders( $query );
	}

	/**
	 * Return an array of refund order IDs for the Merchant Conversions Report CSV.
	 *
	 * @param string  $date
	 * @param integer $limit
	 * @param integer $offset
	 * @return array
	 */
	private function find_refunds( string $date = '', int $limit = -1, int $offset = 0 ): array {
		// Use yesterdays date if no date passed.
		$date = empty( $date ) ? gmdate( 'Y-m-d', strtotime( '-1 day' ) ) : $date;

		$query = [
			'date_created' => $date,
			'limit'        => $limit,
			'offset'       => $offset,
			'return'       => 'ids',
			'type'         => 'shop_order_refund',
		];

		// Get all refunds for the specific day.
		// Meta filtering will be done in find_orders() to leverage meta caching.
		return wc_get_orders( $query );
	}
}
