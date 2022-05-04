<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Countable;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class FilteredProductList
 *
 * A list of filtered products and their total count before filtering.
 *
 * @since 1.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class FilteredProductList implements Countable {

	/**
	 * List of product objects or IDs.
	 *
	 * @var WC_Product[]
	 */
	protected $products = [];

	/**
	 * Count before filtering.
	 *
	 * @var int
	 */
	protected $unfiltered_count;

	/**
	 * FilteredProductList constructor.
	 *
	 * @param WC_Product[] $products         List of filtered products.
	 * @param int          $unfiltered_count Product count before filtering.
	 */
	public function __construct( array $products, int $unfiltered_count ) {
		$this->products         = $products;
		$this->unfiltered_count = $unfiltered_count;
	}

	/**
	 * Get the list of products.
	 *
	 * @return WC_Product[]
	 */
	public function get(): array {
		return $this->products;
	}

	/**
	 * Get product IDs.
	 *
	 * @return int[]
	 */
	public function get_product_ids(): array {
		return array_map(
			function ( $product ) {
				if ( $product instanceof WC_Product ) {
					return $product->get_id();
				}
				return $product;
			},
			$this->products
		);
	}

	/**
	 * Get the unfiltered amount of results.
	 *
	 * @return int
	 */
	public function get_unfiltered_count(): int {
		return $this->unfiltered_count;
	}

	/**
	 * Count products for Countable.
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->products );
	}
}
