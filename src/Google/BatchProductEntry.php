<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Google_Service_ShoppingContent_Product as GoogleProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchProductEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchProductEntry {

	/**
	 * @var int WooCommerce product ID.
	 */
	protected $wc_product_id;

	/**
	 * @var GoogleProduct|null The inserted product. Only defined if the method is insert.
	 */
	protected $google_product;

	/**
	 * BatchProductEntry constructor.
	 *
	 * @param int                $wc_product_id
	 * @param GoogleProduct|null $google_product
	 */
	public function __construct( int $wc_product_id, GoogleProduct $google_product = null ) {
		$this->wc_product_id  = $wc_product_id;
		$this->google_product = $google_product;
	}

	/**
	 * @return int
	 */
	public function get_wc_product_id(): int {
		return $this->wc_product_id;
	}

	/**
	 * @return GoogleProduct
	 */
	public function get_google_product() {
		return $this->google_product;
	}
}
