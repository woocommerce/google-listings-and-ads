<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MapiPriceTrait
 *
 * Builds a Merchant API price ({ amountMicros, currencyCode }) from a decimal
 * amount. Formatting straight from the float to a string avoids truncating large
 * amounts through a 32-bit int cast.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter
 */
trait MapiPriceTrait {

	/**
	 * @param float  $amount
	 * @param string $currency
	 *
	 * @return array
	 */
	protected function mapi_price( float $amount, string $currency ): array {
		return [
			'amountMicros' => sprintf( '%.0f', $amount * 1000000 ),
			'currencyCode' => $currency,
		];
	}
}
