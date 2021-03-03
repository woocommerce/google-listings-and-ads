<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
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
 * @method get_synced_at( int $product_id ): int
 * @method update_google_ids( int $product_id, array $value )
 * @method delete_google_ids( int $product_id )
 * @method get_google_ids( int $product_id ): array
 * @method update_visibility( int $product_id, $value )
 * @method delete_visibility( int $product_id )
 * @method get_visibility( int $product_id ): string
 */
class ProductMetaHandler implements Service, Registerable {

	use PluginHelper;

	public const KEY_SYNCED_AT  = 'synced_at';
	public const KEY_GOOGLE_IDS = 'google_ids';
	public const KEY_VISIBILITY = 'visibility';

	public const VALID_KEYS = [
		self::KEY_SYNCED_AT,
		self::KEY_GOOGLE_IDS,
		self::KEY_VISIBILITY,
	];

	protected const TYPES = [
		self::KEY_SYNCED_AT  => 'int',
		self::KEY_GOOGLE_IDS => 'array',
		self::KEY_VISIBILITY => 'string',
	];

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

		if ( isset( self::TYPES[ $key ] ) ) {
			if ( in_array( self::TYPES[ $key ], [ 'bool', 'boolean' ], true ) ) {
				$value = wc_bool_to_string( $value );
			} else {
				settype( $value, self::TYPES[ $key ] );
			}
		}

		update_post_meta( $product_id, $this->prefix_meta_key( $key ), $value );
	}

	/**
	 * @param int    $product_id
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function delete( int $product_id, string $key ) {
		self::validate_meta_key( $key );

		delete_post_meta( $product_id, $this->prefix_meta_key( $key ) );
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

		$value = get_post_meta( $product_id, $this->prefix_meta_key( $key ), true );

		if ( isset( self::TYPES[ $key ] ) && in_array( self::TYPES[ $key ], [ 'bool', 'boolean' ], true ) ) {
			$value = wc_string_to_bool( $value );
		}

		return $value;
	}

	/**
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	protected static function validate_meta_key( string $key ) {
		if ( ! in_array( $key, self::VALID_KEYS, true ) ) {
			throw InvalidMeta::invalid_key( $key );
		}
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_product_data_store_cpt_get_products_query',
			function ( array $query, array $query_vars ) {
				return $this->handle_query_vars( $query, $query_vars );
			},
			10,
			2
		);
	}

	/**
	 * Handle the WooCommerce product's meta data query vars.
	 *
	 * @hooked handle_query_vars
	 *
	 * @param array $query      Args for WP_Query.
	 * @param array $query_vars Query vars from WC_Product_Query.
	 *
	 * @return array modified $query
	 */
	protected function handle_query_vars( array $query, array $query_vars ): array {
		if ( ! empty( $query_vars['meta_query'] ) ) {
			$meta_query = $this->sanitize_meta_query( $query_vars['meta_query'] );
			if ( ! empty( $meta_query ) ) {
				$query['meta_query'] = array_merge( $query['meta_query'], $meta_query );
			}
		}

		return $query;
	}

	/**
	 * Ensure the 'meta_query' argument passed to self::handle_query_vars is well-formed.
	 *
	 * @param array $queries Array of meta query clauses.
	 *
	 * @return array Sanitized array of meta query clauses.
	 */
	protected function sanitize_meta_query( array $queries ): array {
		$prefixed_valid_keys = array_map( [ $this, 'prefix_meta_key' ], self::VALID_KEYS );
		$clean_queries       = [];

		if ( ! is_array( $queries ) ) {
			return $clean_queries;
		}

		foreach ( $queries as $key => $meta_query ) {
			if ( 'relation' !== $key && ! is_array( $meta_query ) ) {
				continue;
			}

			if ( 'relation' === $key && is_string( $meta_query ) ) {
				$clean_queries[ $key ] = $meta_query;

				// First-order clause.
			} elseif ( isset( $meta_query['key'] ) || isset( $meta_query['value'] ) ) {
				if ( in_array( $meta_query['key'], $prefixed_valid_keys, true ) ) {
					$clean_queries[ $key ] = $meta_query;
				}

				// Otherwise, it's a nested meta_query, so we recurse.
			} else {
				$cleaned_query = $this->sanitize_meta_query( $meta_query );

				if ( ! empty( $cleaned_query ) ) {
					$clean_queries[ $key ] = $cleaned_query;
				}
			}
		}

		return $clean_queries;
	}
}
