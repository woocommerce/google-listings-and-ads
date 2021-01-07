<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

/**
 * Interface BatchProductResponse
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
interface BatchProductResponse {
	/**
	 * @return array|null
	 */
	public function get_products();

	/**
	 * @return array|null
	 */
	public function get_errors();
}
