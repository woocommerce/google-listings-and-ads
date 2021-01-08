<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Google_Service_ShoppingContent_Product as GoogleProduct;

/**
 * Class BatchUpdateProductResponse
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchUpdateProductResponse implements BatchProductResponse {

	/**
	 * @var GoogleProduct[]
	 */
	protected $updated_products;

	/**
	 * @var InvalidProductEntry[]
	 */
	protected $invalid_products;

	/**
	 * BatchUpdateProductResponse constructor.
	 *
	 * @param GoogleProduct[]       $updated_products
	 * @param InvalidProductEntry[] $invalid_products
	 */
	public function __construct( $updated_products = null, $invalid_products = null ) {
		$this->updated_products = $updated_products;
		$this->invalid_products = $invalid_products;
	}

	/**
	 * @return GoogleProduct[]
	 */
	public function get_updated_products() {
		return $this->updated_products;
	}

	/**
	 * @param GoogleProduct[] $updated_products
	 *
	 * @return BatchUpdateProductResponse
	 */
	public function set_updated_products( $updated_products ): BatchUpdateProductResponse {
		$this->updated_products = $updated_products;

		return $this;
	}

	/**
	 * @return InvalidProductEntry[]
	 */
	public function get_invalid_products() {
		return $this->invalid_products;
	}

	/**
	 * @param InvalidProductEntry[] $invalid_products
	 *
	 * @return BatchUpdateProductResponse
	 */
	public function set_invalid_products( $invalid_products ): BatchUpdateProductResponse {
		$this->invalid_products = $invalid_products;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function get_products() {
		return $this->get_updated_products();
	}

	/**
	 * @return array|null
	 */
	public function get_errors() {
		return $this->get_invalid_products();
	}
}
