<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

/**
 * Class BatchDeleteProductResponse
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchDeleteProductResponse implements BatchProductResponse {

	/**
	 * @var BatchProductEntry[]|null Products that were successfully deleted.
	 */
	protected $deleted_products;

	/**
	 * @var BatchInvalidProductEntry[]|null
	 */
	protected $invalid_products;

	/**
	 * BatchDeleteProductResponse constructor.
	 *
	 * @param BatchProductEntry[]|null        $deleted_product_ids
	 * @param BatchInvalidProductEntry[]|null $invalid_products
	 */
	public function __construct( $deleted_product_ids = null, $invalid_products = null ) {
		$this->deleted_products = $deleted_product_ids;
		$this->invalid_products = $invalid_products;
	}

	/**
	 * @return BatchProductEntry[]
	 */
	public function get_deleted_products() {
		return $this->deleted_products;
	}

	/**
	 * @param BatchProductEntry[]|null $deleted_products
	 *
	 * @return BatchDeleteProductResponse
	 */
	public function set_deleted_products( $deleted_products ): BatchDeleteProductResponse {
		$this->deleted_products = $deleted_products;

		return $this;
	}

	/**
	 * @return BatchInvalidProductEntry[]
	 */
	public function get_invalid_products() {
		return $this->invalid_products;
	}

	/**
	 * @param BatchInvalidProductEntry[] $invalid_products
	 *
	 * @return BatchDeleteProductResponse
	 */
	public function set_invalid_products( $invalid_products ): BatchDeleteProductResponse {
		$this->invalid_products = $invalid_products;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function get_products() {
		return $this->get_deleted_products();
	}

	/**
	 * @return array|null
	 */
	public function get_errors() {
		return $this->get_invalid_products();
	}
}
