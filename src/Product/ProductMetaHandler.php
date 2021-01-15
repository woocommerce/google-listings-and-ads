<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use BadMethodCallException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductMetaHandler
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 *
 * @method update_synced_at( int $product_id, $value )
 * @method delete_synced_at( int $product_id )
 * @method get_synced_at( int $product_id )
 * @method update_google_ids( int $product_id, array $value )
 * @method delete_google_ids( int $product_id )
 * @method get_google_ids( int $product_id ): array
 */
class ProductMetaHandler implements Service {

	use PluginHelper;

	public const KEY_SYNCED_AT  = 'synced_at';
	public const KEY_GOOGLE_IDS = 'google_ids';

	/**
	 * @param string $name
	 * @param mixed  $arguments
	 *
	 * @return mixed
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

		// set the value as the third argument if method is `update`
		if ( 'update' === $method ) {
			$arguments[2] = $arguments[1];
		}
		// set the key as the second argument
		$arguments[1] = $key;

		return call_user_func_array( [ $this, $method ], $arguments );
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

		update_post_meta( $product_id, $this->generate_meta_key( $key ), $value );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function delete( int $product_id, string $key ) {
		self::validate_meta_key( $key );

		delete_post_meta( $product_id, $this->generate_meta_key( $key ) );
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

		return get_post_meta( $product_id, $this->generate_meta_key( $key ), true );
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected function generate_meta_key( string $key ): string {
		return "{$this->get_meta_key_prefix()}_{$key}";
	}

	/**
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	protected static function validate_meta_key( string $key ) {
		$valid_keys = [
			self::KEY_SYNCED_AT,
			self::KEY_GOOGLE_IDS,
		];

		if ( ! in_array( $key, $valid_keys, true ) ) {
			throw InvalidMeta::invalid_key( $key );
		}
	}
}
