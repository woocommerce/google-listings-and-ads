<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchProductResponse
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchProductResponse {
	/**
	 * @var BatchProductEntry[] Products that were successfully updated, deleted or retrieved.
	 */
	protected $products;

	/**
	 * @var BatchInvalidProductEntry[]
	 */
	protected $errors;

	/**
	 * BatchProductResponse constructor.
	 *
	 * @param BatchProductEntry[]        $products
	 * @param BatchInvalidProductEntry[] $errors
	 */
	public function __construct( array $products, array $errors ) {
		$this->products = $products;
		$this->errors   = $errors;
	}
	/**
	 * @return BatchProductEntry[]
	 */
	public function get_products(): array {
		return $this->products;
	}

	/**
	 * @return BatchInvalidProductEntry[]
	 */
	public function get_errors(): array {
		return $this->errors;
	}
}
