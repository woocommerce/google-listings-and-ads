<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Google_Service_ShoppingContent_Product as GoogleProduct;

/**
 * Class BatchProductEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchProductEntry {

	/**
	 * @var int|null WooCommerce product ID.
	 */
	protected $wc_product_id;

	/**
	 * @var GoogleProduct|null The inserted product. Only defined if the method is insert and if the request was successful.
	 */
	protected $google_product;

	/**
	 * BatchProductEntry constructor.
	 *
	 * @param int|null           $wc_product_id
	 * @param GoogleProduct|null $google_product
	 */
	public function __construct( $wc_product_id = null, $google_product = null ) {
		$this->wc_product_id  = $wc_product_id;
		$this->google_product = $google_product;
	}

	/**
	 * @return int|null
	 */
	public function get_wc_product_id() {
		return $this->wc_product_id;
	}

	/**
	 * @param int|null $wc_product_id
	 *
	 * @return BatchProductEntry
	 */
	public function set_wc_product_id( $wc_product_id ): BatchProductEntry {
		$this->wc_product_id = $wc_product_id;

		return $this;
	}

	/**
	 * @return GoogleProduct|null
	 */
	public function get_google_product() {
		return $this->google_product;
	}

	/**
	 * @param GoogleProduct|null $google_product
	 *
	 * @return BatchProductEntry
	 */
	public function set_google_product( $google_product ): BatchProductEntry {
		$this->google_product = $google_product;

		return $this;
	}
}
