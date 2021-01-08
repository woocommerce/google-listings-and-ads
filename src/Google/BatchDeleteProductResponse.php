<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

/**
 * Class BatchDeleteProductResponse
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchDeleteProductResponse implements BatchProductResponse {

	/**
	 * @var string[]|null Google product IDs that were successfully deleted.
	 */
	protected $deleted_product_ids;

	/**
	 * @var InvalidProductEntry[]|null
	 */
	protected $invalid_products;

	/**
	 * BatchDeleteProductResponse constructor.
	 *
	 * @param string[]|null              $deleted_product_ids
	 * @param InvalidProductEntry[]|null $invalid_products
	 */
	public function __construct( $deleted_product_ids = null, $invalid_products = null ) {
		$this->deleted_product_ids = $deleted_product_ids;
		$this->invalid_products    = $invalid_products;
	}

	/**
	 * @return string[]
	 */
	public function get_deleted_product_ids() {
		return $this->deleted_product_ids;
	}

	/**
	 * @param string[] $deleted_product_ids
	 *
	 * @return BatchDeleteProductResponse
	 */
	public function set_deleted_product_ids( $deleted_product_ids ): BatchDeleteProductResponse {
		$this->deleted_product_ids = $deleted_product_ids;

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
		return $this->get_deleted_product_ids();
	}

	/**
	 * @return array|null
	 */
	public function get_errors() {
		return $this->get_invalid_products();
	}
}
