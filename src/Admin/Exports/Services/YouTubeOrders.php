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
	public function find_orders( string $date = '', int $limit = -1, int $offset = 1 ): array {
		// Use yesterdays date if no date passed.
		$date = empty( $date ) ? gmdate( 'Y-m-d', strtotime( '-1 day' ) ) : $date;

		// Query orders with the YouTube attribution source.
		$query = [
			'date_created' => $date,
			'limit'        => $limit,
			'offset'       => $offset,
			'type'         => [
				'shop_order',
				'shop_order_refund',
			],
			'meta_query'   => [
				[
					'key'     => '_wc_order_attribution_utm_source',
					'value'   => 'youtube',
					'compare' => '=',
				],
			],
		];

		$orders = wc_get_orders( $query );

		// Map order objects to an array of IDs.
		return array_map(
			function ( $order ) {
				return $order->get_ID();
			},
			$orders
		);
	}
}
