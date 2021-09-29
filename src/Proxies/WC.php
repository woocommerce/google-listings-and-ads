<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use WC_Countries;
use WC_Coupon;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class WC {

	/**
	 * The base location for the store.
	 *
	 * @var string
	 */
	protected $base_country;

	/**
	 * @var array
	 */
	protected $countries;

	/** @var WC_Countries */
	protected $wc_countries;

	/**
	 * WC constructor.
	 *
	 * @param WC_Countries|null $countries
	 */
	public function __construct( ?WC_Countries $countries = null ) {
		$countries          = $countries ?? new WC_Countries();
		$this->wc_countries = $countries;
		$this->base_country = $countries->get_base_country() ?? 'US';
		$this->countries    = $countries->get_countries() ?? [];
	}

	/**
	 * Get WooCommerce
	 *
	 * @return array
	 */
	public function get_countries(): array {
		return $this->countries;
	}

	/**
	 * Get the base country for the store.
	 *
	 * @return string
	 */
	public function get_base_country(): string {
		return $this->base_country;
	}

	/**
	 * Get the WC_Countries object
	 *
	 * @return WC_Countries
	 */
	public function get_wc_countries(): WC_Countries {
		return $this->wc_countries;
	}

	/**
	 * Get a WooCommerce product and confirm it exists.
	 *
	 * @param int $product_id
	 *
	 * @return WC_Product
	 *
	 * @throws InvalidValue When the product does not exist.
	 */
	public function get_product( int $product_id ): WC_Product {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			throw InvalidValue::not_valid_product_id( $product_id );
		}

		return $product;
	}

	/**
	 * Get a WooCommerce product if it exists or return null if it doesn't
	 *
	 * @param int $product_id
	 *
	 * @return WC_Product|null
	 */
	public function maybe_get_product( int $product_id ): ?WC_Product {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		return $product;
	}

	/**
	 * Get a WooCommerce coupon if it exists or return null if it doesn't
	 *
	 * @param int $coupon_id
	 *
	 * @return WC_Coupon|null
	 */
	public function maybe_get_coupon( int $coupon_id ): ?WC_Coupon {
		$coupon = new WC_Coupon( $coupon_id );
		if ( $coupon->get_id() === 0 ) {
			return null;
		}
		return $coupon;
	}
}
