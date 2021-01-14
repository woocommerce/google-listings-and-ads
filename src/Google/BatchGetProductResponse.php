<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

/**
 * Class BatchGetProductResponse
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchGetProductResponse implements BatchProductResponse {

	/**
	 * @var BatchProductEntry[]
	 */
	protected $products;

	/**
	 * @var array|null
	 */
	protected $errors;

	/**
	 * BatchGetProductResponse constructor.
	 *
	 * @param BatchProductEntry[]|null $products
	 * @param array|null               $errors
	 */
	public function __construct( $products = null, $errors = null ) {
		$this->products = $products;
		$this->errors   = $errors;
	}

	/**
	 * @return BatchProductEntry[]|null
	 */
	public function get_products() {
		return $this->products;
	}

	/**
	 * @param BatchProductEntry[]|null $products
	 *
	 * @return BatchGetProductResponse
	 */
	public function set_products( $products ): BatchGetProductResponse {
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
	 * @return BatchGetProductResponse
	 */
	public function set_errors( $errors ): BatchGetProductResponse {
		$this->errors = $errors;

		return $this;
	}
}
