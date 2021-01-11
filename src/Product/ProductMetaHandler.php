<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use BadMethodCallException;

/**
 * Class ProductMetaHandler
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 *
 * @method update_synced_at( int $product_id, $value )
 * @method delete_synced_at( int $product_id )
 * @method get_synced_at( int $product_id )
 * @method update_google_id( int $product_id, $value )
 * @method delete_google_id( int $product_id )
 * @method get_google_id( int $product_id )
 */
class ProductMetaHandler implements Service {

	protected const KEY_PREFIX = '_wc_gla_';

	public const KEY_SYNCED_AT = 'synced_at';
	public const KEY_GOOGLE_ID = 'google_id';

	/**
	 * @param string $name
	 * @param mixed  $arguments
	 *
	 * @throws BadMethodCallException If the method that's called doesn't exist.
	 * @throws InvalidMeta            If the meta key is invalid.
	 */
	public function __call( $name, $arguments ) {
		$found_matches = preg_match( '/^([a-z]+)_([\w\d]+)$/i', $name, $matches );

		if ( ! $found_matches ) {
			throw new BadMethodCallException( sprintf( 'The method %s does not exist in class ProductMetaHandler', $name ) );
		}

		list( $function_name, $method, $key ) = $matches;

		// validate the method
		if ( ! in_array( $method, [ 'update', 'delete', 'get' ], true ) ) {
			throw new BadMethodCallException( sprintf( 'The method %s does not exist in class ProductMetaHandler', $function_name ) );
		}

		$arguments['key'] = $key;
		call_user_func_array( [ $this, $method ], $arguments );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function update( int $product_id, string $key, $value ) {
		self::validate_meta_key( $key );

		update_post_meta( $product_id, self::generate_meta_key( $key ), $value );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function delete( int $product_id, string $key ) {
		self::validate_meta_key( $key );

		delete_post_meta( $product_id, self::generate_meta_key( $key ) );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 *
	 * @return mixed The value
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function get( int $product_id, string $key ) {
		self::validate_meta_key( $key );

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

	/**
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	protected static function validate_meta_key( string $key ) {
		$valid_keys = [
			self::KEY_SYNCED_AT,
			self::KEY_GOOGLE_ID,
		];

		if ( ! in_array( $key, $valid_keys, true ) ) {
			throw InvalidMeta::invalid_key( $key );
		}
	}
}
