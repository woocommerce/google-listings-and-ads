<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ProductIDMap;

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
	 * @return WCProductAdapter|string
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * @param ProductIDMap $product_id_map
	 *
	 * @return BatchProductRequestEntry[]
	 */
	public static function create_from_id_map( ProductIDMap $product_id_map ): array {
		$product_entries = [];
		foreach ( $product_id_map as $google_product_id => $wc_product_id ) {
			$product_entries[] = new BatchProductRequestEntry( $wc_product_id, $google_product_id );
		}

		return $product_entries;
	}

	/**
	 * @param BatchProductRequestEntry[] $request_entries
	 *
	 * @return ProductIDMap $product_id_map
	 */
	public static function convert_to_id_map( array $request_entries ): ProductIDMap {
		$id_map = [];
		foreach ( $request_entries as $request_entry ) {
			$id_map[ (string) $request_entry->get_product() ] = $request_entry->get_wc_product_id();
		}

		return new ProductIDMap( $id_map );
	}
}
