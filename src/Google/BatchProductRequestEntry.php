<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;

/**
 * Class BatchProductRequestEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchProductRequestEntry {
	/**
	 * @var int
	 */
	protected $wc_product_id;

	/**
	 * @var WCProductAdapter|string The Google product object or its REST ID.
	 */
	protected $product;

	/**
	 * BatchProductRequestEntry constructor.
	 *
	 * @param int                     $wc_product_id
	 * @param WCProductAdapter|string $product
	 */
	public function __construct( int $wc_product_id, $product ) {
		$this->wc_product_id = $wc_product_id;
		$this->product       = $product;
	}

	/**
	 * @return int
	 */
	public function get_wc_product_id(): int {
		return $this->wc_product_id;
	}

	/**
	 * @param int $wc_product_id
	 *
	 * @return BatchProductRequestEntry
	 */
	public function set_wc_product_id( int $wc_product_id ): BatchProductRequestEntry {
		$this->wc_product_id = $wc_product_id;

		return $this;
	}

	/**
	 * @return WCProductAdapter|string
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * @param WCProductAdapter|string $product
	 *
	 * @return BatchProductRequestEntry
	 */
	public function set_product( $product ): BatchProductRequestEntry {
		$this->product = $product;

		return $this;
	}
}
