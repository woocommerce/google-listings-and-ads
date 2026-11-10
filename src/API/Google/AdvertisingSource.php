<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

defined( 'ABSPATH' ) || exit;

/**
 * What a campaign advertises: the Merchant Center product feed or a page on the store.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
final class AdvertisingSource {

	/**
	 * The campaign uses the Merchant Center product feed.
	 *
	 * @var string
	 */
	public const PRODUCT = 'product';

	/**
	 * The campaign promotes a page on the store and does not use the product feed.
	 *
	 * @var string
	 */
	public const PAGE = 'page';

	/**
	 * Get every accepted advertising source.
	 *
	 * @return string[]
	 */
	public static function values(): array {
		return [ self::PRODUCT, self::PAGE ];
	}

	/**
	 * Get the advertising source used when a request doesn't choose one.
	 *
	 * @param int $merchant_id The connected Merchant Center ID, or 0 when none is connected.
	 *
	 * @return string
	 */
	public static function get_default( int $merchant_id ): string {
		return $merchant_id > 0 ? self::PRODUCT : self::PAGE;
	}
}
