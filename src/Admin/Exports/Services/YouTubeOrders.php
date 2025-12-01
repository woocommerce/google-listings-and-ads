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

		// combine IDs from purchase and refund queries.
		$purchases = $this->find_purchases( $date, $limit, $offset );
		$refunds   = $this->find_refunds( $date, $limit, $offset );

		return array_unique( array_merge( $purchases, $refunds ) );
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

		// Query orders with the YouTube attribution source.
		$query = [
			'date_created' => $date,
			'limit'        => $limit,
			'offset'       => $offset,
			'return'       => 'ids',
			'type'         => 'shop_order',
			'meta_query'   => [
				[
					'key'     => '_wc_order_attribution_utm_source',
					'value'   => 'youtube',
					'compare' => '=',
				],
			],
		];

		return wc_get_orders( $query );
	}

	/**
	 * Return an array of refund order IDs for the Merchant Conversions Report CSV.
	 *
	 * @param string $date
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
			'type'         => 'shop_order_refund',
		];

		// Get all refunds for the specific day.
		$refunds = wc_get_orders( $query );

		// Check the parent order for the youtube attribution.
		$refund_ids = array_map(
			function( $refund ) {
				$order = wc_get_order( $refund->get_parent_id() );

				if ( 'youtube' === $order->get_meta( '_wc_order_attribution_utm_source' ) ) {
					return $refund->get_id();
				}

				return null;
			},
			$refunds
		);

		return array_filter( $refund_ids );
	}
}
