<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\ProductIDMap;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchDeleteProductRequestEntry
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class BatchProductIDRequestEntry {
	/**
	 * @var int
	 */
	protected $wc_product_id;

	/**
	 * @var string The Google product REST ID.
	 */
	protected $product_id;

	/**
	 * BatchDeleteProductRequestEntry constructor.
	 *
	 * @param int    $wc_product_id
	 * @param string $product
	 */
	public function __construct( int $wc_product_id, string $product ) {
		$this->wc_product_id = $wc_product_id;
		$this->product_id    = $product;
	}

	/**
	 * @return int
	 */
	public function get_wc_product_id(): int {
		return $this->wc_product_id;
	}

	/**
	 * @return string
	 */
	public function get_product_id(): string {
		return $this->product_id;
	}

	/**
	 * @param ProductIDMap $product_id_map
	 *
	 * @return BatchProductIDRequestEntry[]
	 */
	public static function create_from_id_map( ProductIDMap $product_id_map ): array {
		$product_entries = [];
		foreach ( $product_id_map as $google_product_id => $wc_product_id ) {
			$product_entries[] = new BatchProductIDRequestEntry( $wc_product_id, $google_product_id );
		}

		return $product_entries;
	}

	/**
	 * @param BatchProductIDRequestEntry[] $request_entries
	 *
	 * @return ProductIDMap $product_id_map
	 */
	public static function convert_to_id_map( array $request_entries ): ProductIDMap {
		$id_map = [];
		foreach ( $request_entries as $request_entry ) {
			$id_map[ $request_entry->get_product_id() ] = $request_entry->get_wc_product_id();
		}

		return new ProductIDMap( $id_map );
	}
}
