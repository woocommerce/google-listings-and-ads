<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;

defined( 'ABSPATH' ) || exit;

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
	 * @var WCProductAdapter The Google product object
	 */
	protected $product;

	/**
	 * BatchProductRequestEntry constructor.
	 *
	 * @param int              $wc_product_id
	 * @param WCProductAdapter $product
	 */
	public function __construct( int $wc_product_id, WCProductAdapter $product ) {
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
	 * @return WCProductAdapter
	 */
	public function get_product(): WCProductAdapter {
		return $this->product;
	}
}
