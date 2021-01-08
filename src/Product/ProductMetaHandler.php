<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class ProductMetaHandler
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductMetaHandler implements Service {

	protected const KEY_PREFIX = '_wc_gla_';

	public const KEY_SYNCED_AT = 'synced_at';
	public const KEY_GOOGLE_ID = 'google_id';

	/**
	 * @param int    $product_id
	 * @param string $key
	 * @param mixed  $value
	 */
	public function update( int $product_id, string $key, $value ) {
		update_post_meta( $product_id, self::generate_meta_key( $key ), $value );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 */
	public function delete( int $product_id, string $key ) {
		delete_post_meta( $product_id, self::generate_meta_key( $key ) );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 *
	 * @return mixed The value
	 */
	public function get( int $product_id, string $key ) {
		return get_post_meta( $product_id, self::generate_meta_key( $key ), true );
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected static function generate_meta_key( string $key ): string {
		return self::KEY_PREFIX . $key;
	}
}
