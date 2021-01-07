<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class ProductMetaHandler
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductMetaHandler implements Service {

	protected const PRODUCT_META_KEY = 'woocommerce_gla_data';

	public const KEY_SYNCED_AT = 'synced_at';
	public const KEY_GOOGLE_ID = 'google_id';

	/**
	 * @param int    $product_id
	 * @param string $key
	 * @param mixed  $value
	 */
	public function update( int $product_id, string $key, $value ) {
		$product_meta = $this->get_all( $product_id );

		$product_meta[ $key ] = $value;

		update_post_meta( $product_id, self::PRODUCT_META_KEY, $product_meta );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 */
	public function delete( int $product_id, string $key ) {
		$product_meta = $this->get_all( $product_id );

		unset( $product_meta[ $key ] );

		update_post_meta( $product_id, self::PRODUCT_META_KEY, $product_meta );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 *
	 * @return mixed The value
	 */
	public function get( int $product_id, string $key ) {
		$product_meta = $this->get_all( $product_id );

		return $product_meta[ $key ] ?? null;
	}

	/**
	 * @param int $product_id
	 *
	 * @return array
	 */
	public function get_all( int $product_id ) {
		$product_meta = get_post_meta( $product_id, self::PRODUCT_META_KEY, true );

		return is_array( $product_meta ) ? $product_meta : [];
	}
}
