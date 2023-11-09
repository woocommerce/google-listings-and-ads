<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class WooCoommerceProxy
 *
 * Initializes the hooks to filter the data sent to the proxy depending on the query parameter gla_syncable.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WooCoommerceProxy implements Service, Registerable, Conditional {

	/**
	 * The Post types to be filtered.
	 *
	 * @var array
	 */
	public static $post_types_to_be_filter = [
		'product'           => [
			'meta_query' => [
				[
					'key'     => '_wc_gla_visibility',
					'value'   => 'sync-and-show',
					'compare' => '=',
				],
			],
		],
		'shop_coupon'       => [
			'meta_query' => [
				[
					'key'     => '_wc_gla_visibility',
					'value'   => 'sync-and-show',
					'compare' => '=',
				],
				[
					'key'     => 'customer_email',
					'compare' => 'NOT EXISTS',
				],
			],
		],
		'product_variation' => [
			'meta_query' => null,
		],
	];

	/**
	 * Check if the request is a API request.
	 *
	 * @return bool Whether the class is needed.
	 */
	public static function is_needed(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return self::is_request_to_rest_api() && isset( $_GET['gla_syncable'] );
	}

	/**
	 * Check if is request to the WC REST API.
	 *
	 * This function is based on the WooCommerce function is_rest_api_request() but also checks for rest_route=/wc/ regardless the permalink settings.
	 *
	 * @see https://github.com/woocommerce/woocommerce/blob/bae3a34501047bfdff4d79c240396f38cdcd1f25/plugins/woocommerce/includes/class-wc-rest-authentication.php#L62
	 * @see https://github.com/woocommerce/woocommerce/issues/41191
	 *
	 * @return bool Whether the request is to the WC REST API.
	 */
	public static function is_request_to_rest_api(): bool {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		// Check if the request is to the WC API endpoints.
		return ( false !== strpos( $request_uri, $rest_prefix . 'wc/' ) ) || ( false !== strpos( $request_uri, 'rest_route=/wc/' ) );
	}

	/**
	 * Register all filters.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_rest_query_vars',
			function( $valid_vars ) {
				$valid_vars[] = 'gla_syncable';
				return $valid_vars;
			}
		);

		foreach ( array_keys( self::$post_types_to_be_filter ) as $object_type ) {
			$this->register_object_types_filter( $object_type );
		}

	}

	/**
	 * Register the filters for a specific object type.
	 *
	 * @param string $object_type The object type.
	 */
	protected function register_object_types_filter( string $object_type ): void {
		add_filter( 'woocommerce_rest_prepare_' . $object_type . '_object', [ $this, 'check_item_is_syncable' ], 9, 3 );

		add_filter(
			'woocommerce_rest_prepare_' . $object_type . '_object',
			[ $this, 'filter_metadata' ],
			10,
			3
		);

		add_filter(
			'woocommerce_rest_' . $object_type . '_object_query',
			[ $this, 'filter_by_metaquery' ],
			10,
			2
		);

	}

	/**
	 * Check if a single item is syncable.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param mixed            $object   The object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response The response object updated.
	 */
	public function check_item_is_syncable( $response, $object, $request ): WP_REST_Response {
		$route               = $request->get_route();
		$pattern             = '/(?P<resource>[\w]+)\/(?P<id>[\d]+)/';
		$protected_resources = [
			'products',
			'coupons',
		];
		preg_match( $pattern, $route, $matches );

		if ( ! isset( $matches['id'] ) || ! isset( $matches['resource'] ) || ! in_array( $matches['resource'], $protected_resources, true ) ) {
			return $response;
		}

		$meta_data = $response->get_data()['meta_data'] ?? [];

		foreach ( $meta_data as $meta ) {
			if ( $meta->key === '_wc_gla_visibility' && $meta->value === 'sync-and-show' ) {
				return $response;
			}
		}

		return new WP_REST_Response(
			[
				'code'    => 'gla_rest_item_no_syncable',
				'message' => 'Item not syncable',
				'data'    => [
					'status' => '403',
				],
			],
			403
		);

	}

	/**
	 * Query items with specific args for example where _wc_gla_visibility is set to sync-and-show.
	 *
	 * @param array $args The query args.
	 *
	 * @return array The query args updated.
	 * */
	public function filter_by_metaquery( array $args ): array {
		$post_type         = $args['post_type'];
		$post_type_filters = self::$post_types_to_be_filter[ $post_type ];

		if ( ! isset( $post_type_filters['meta_query'] ) || ! is_array( $post_type_filters['meta_query'] ) ) {
			return $args;
		}

		$args['meta_query'] = [ ...$args['meta_query'] ?? [], ...$post_type_filters['meta_query'] ];

		return $args;
	}

	/**
	 * Filter the metadata of an object.
	 *
	 * @param WP_REST_Response $response The response object.
	 *
	 * @return WP_REST_Response The response object updated.
	 */
	public function filter_metadata( WP_REST_Response $response ): WP_REST_Response {
		$data = $response->get_data();

		if ( ! isset( $data['meta_data'] ) ) {
			return $response;
		}

		foreach ( $data['meta_data'] as $key => $meta ) {
			if ( str_starts_with( $meta->key, '_' ) && ! str_starts_with( $meta->key, '_wc_gla' ) ) {
				unset( $data['meta_data'][ $key ] );
			}
		}

		$data['meta_data'] = array_values( $data['meta_data'] );
		$response->set_data( $data );

		return $response;
	}

}
