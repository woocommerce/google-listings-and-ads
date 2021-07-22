<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use BadMethodCallException;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductMetaHandler
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 *
 * @method update_synced_at( WC_Product $product, $value )
 * @method delete_synced_at( WC_Product $product )
 * @method get_synced_at( WC_Product $product ): int|null
 * @method update_google_ids( WC_Product $product, array $value )
 * @method delete_google_ids( WC_Product $product )
 * @method get_google_ids( WC_Product $product ): array|null
 * @method update_visibility( WC_Product $product, $value )
 * @method delete_visibility( WC_Product $product )
 * @method get_visibility( WC_Product $product ): string|null
 * @method update_errors( WC_Product $product, array $value )
 * @method delete_errors( WC_Product $product )
 * @method get_errors( WC_Product $product ): array|null
 * @method update_failed_sync_attempts( WC_Product $product, int $value )
 * @method delete_failed_sync_attempts( WC_Product $product )
 * @method get_failed_sync_attempts( WC_Product $product ): int|null
 * @method update_sync_failed_at( WC_Product $product, int $value )
 * @method delete_sync_failed_at( WC_Product $product )
 * @method get_sync_failed_at( WC_Product $product ): int|null
 * @method update_sync_status( WC_Product $product, string $value )
 * @method delete_sync_status( WC_Product $product )
 * @method get_sync_status( WC_Product $product ): string|null
 * @method update_mc_status( WC_Product $product, string $value )
 * @method delete_mc_status( WC_Product $product )
 * @method get_mc_status( WC_Product $product ): string|null
 */
class ProductMetaHandler implements Service, Registerable {

	use PluginHelper;

	public const KEY_SYNCED_AT            = 'synced_at';
	public const KEY_GOOGLE_IDS           = 'google_ids';
	public const KEY_VISIBILITY           = 'visibility';
	public const KEY_ERRORS               = 'errors';
	public const KEY_FAILED_SYNC_ATTEMPTS = 'failed_sync_attempts';
	public const KEY_SYNC_FAILED_AT       = 'sync_failed_at';
	public const KEY_SYNC_STATUS          = 'sync_status';
	public const KEY_MC_STATUS            = 'mc_status';

	protected const TYPES = [
		self::KEY_SYNCED_AT            => 'int',
		self::KEY_GOOGLE_IDS           => 'array',
		self::KEY_VISIBILITY           => 'string',
		self::KEY_ERRORS               => 'array',
		self::KEY_FAILED_SYNC_ATTEMPTS => 'int',
		self::KEY_SYNC_FAILED_AT       => 'int',
		self::KEY_SYNC_STATUS          => 'string',
		self::KEY_MC_STATUS            => 'string',
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
	public function __call( string $name, $arguments ) {
		$found_matches = preg_match( '/^([a-z]+)_([\w\d]+)$/i', $name, $matches );

		if ( ! $found_matches ) {
			throw new BadMethodCallException( sprintf( 'The method %s does not exist in class ProductMetaHandler', $name ) );
		}

		[ $function_name, $method, $key ] = $matches;

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
	 * @param WC_Product $product
	 * @param string     $key
	 * @param mixed      $value
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function update( WC_Product $product, string $key, $value ) {
		self::validate_meta_key( $key );

		if ( isset( self::TYPES[ $key ] ) ) {
			if ( in_array( self::TYPES[ $key ], [ 'bool', 'boolean' ], true ) ) {
				$value = wc_bool_to_string( $value );
			} else {
				settype( $value, self::TYPES[ $key ] );
			}
		}

		$product->update_meta_data( $this->prefix_meta_key( $key ), $value );
		$product->save_meta_data();
	}

	/**
	 * @param WC_Product $product
	 * @param string     $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function delete( WC_Product $product, string $key ) {
		self::validate_meta_key( $key );

		$product->delete_meta_data( $this->prefix_meta_key( $key ) );
		$product->save_meta_data();
	}

	/**
	 * @param WC_Product $product
	 * @param string     $key
	 *
	 * @return mixed The value, or null if the meta key doesn't exist.
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function get( WC_Product $product, string $key ) {
		self::validate_meta_key( $key );

		$value = null;
		if ( $product->meta_exists( $this->prefix_meta_key( $key ) ) ) {
			$value = $product->get_meta( $this->prefix_meta_key( $key ), true );

			if ( isset( self::TYPES[ $key ] ) && in_array( self::TYPES[ $key ], [ 'bool', 'boolean' ], true ) ) {
				$value = wc_string_to_bool( $value );
			}
		}

		return $value;
	}

	/**
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	protected static function validate_meta_key( string $key ) {
		if ( ! self::is_meta_key_valid( $key ) ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Product meta key is invalid: %s', $key ),
				__METHOD__
			);

			throw InvalidMeta::invalid_key( $key );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return bool Whether the meta key is valid.
	 */
	public static function is_meta_key_valid( string $key ): bool {
		return isset( self::TYPES[ $key ] );
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
	protected function sanitize_meta_query( $queries ): array {
		$prefixed_valid_keys = array_map( [ $this, 'prefix_meta_key' ], array_keys( self::TYPES ) );
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

	/**
	 * @param array $meta_queries
	 *
	 * @return array
	 */
	public function prefix_meta_query_keys( $meta_queries ): array {
		$updated_queries = [];
		if ( ! is_array( $meta_queries ) ) {
			return $updated_queries;
		}

		foreach ( $meta_queries as $key => $meta_query ) {
			// First-order clause.
			if ( 'relation' === $key && is_string( $meta_query ) ) {
				$updated_queries[ $key ] = $meta_query;

				// First-order clause.
			} elseif ( isset( $meta_query['key'] ) || isset( $meta_query['value'] ) ) {
				if ( self::is_meta_key_valid( $meta_query['key'] ) ) {
					$meta_query['key'] = $this->prefix_meta_key( $meta_query['key'] );
				}
			} else {
				// Otherwise, it's a nested meta_query, so we recurse.
				$meta_query = $this->prefix_meta_query_keys( $meta_query );
			}

			$updated_queries[ $key ] = $meta_query;
		}

		return $updated_queries;
	}

	/**
	 * Returns all available meta keys.
	 *
	 * @return array
	 */
	public static function get_all_meta_keys(): array {
		return array_keys( self::TYPES );
	}
}
