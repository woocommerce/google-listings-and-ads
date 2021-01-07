<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Google_Service_ShoppingContent_Product as GoogleProduct;

/**
 * Class BatchGetProductResponse
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchGetProductProductResponse implements BatchProductResponse {

	/**
	 * @var GoogleProduct[]
	 */
	protected $products;

	/**
	 * @var array|null
	 */
	protected $errors;

	/**
	 * BatchGetProductResponse constructor.
	 *
	 * @param GoogleProduct[]|null $products
	 * @param array|null           $errors
	 */
	public function __construct( $products = null, $errors = null ) {
		$this->products = $products;
		$this->errors   = $errors;
	}

	/**
	 * @return GoogleProduct[]|null
	 */
	public function get_products() {
		return $this->products;
	}

	/**
	 * @param GoogleProduct[]|null $products
	 *
	 * @return BatchGetProductProductResponse
	 */
	public function set_products( $products ): BatchGetProductProductResponse {
		$this->products = $products;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * @param array|null $errors
	 *
	 * @return BatchGetProductProductResponse
	 */
	public function set_errors( $errors ): BatchGetProductProductResponse {
		$this->errors = $errors;

		return $this;
	}
}
