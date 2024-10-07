<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use WC_Product;
use WP_REST_Response;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPCOMProxy
 *
 * Initializes the hooks to filter the data sent to the WPCOM proxy depending on the query parameter gla_syncable.
 *
 * @since 2.8.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WPCOMProxy implements Service, Registerable, OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The ShippingTimeQuery object.
	 *
	 * @var ShippingTimeQuery
	 */
	protected $shipping_time_query;

	/**
	 * The AttributeManager object.
	 *
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * The protected resources. Only items with visibility set to sync-and-show will be returned.
	 */
	protected const PROTECTED_RESOURCES = [
		'products',
		'coupons',
	];

	/**
	 * WPCOMProxy constructor.
	 *
	 * @param ShippingTimeQuery $shipping_time_query The ShippingTimeQuery object.
	 * @param AttributeManager  $attribute_manager   The AttributeManager object.
	 */
	public function __construct( ShippingTimeQuery $shipping_time_query, AttributeManager $attribute_manager ) {
		$this->shipping_time_query = $shipping_time_query;
		$this->attribute_manager   = $attribute_manager;
	}

	/**
	 * The meta key used to filter the items.
	 *
	 * @var string
	 */
	public const KEY_VISIBILITY = '_wc_gla_visibility';

	/**
	 * The Post types to be filtered.
	 *
	 * @var array
	 */
	public static $post_types_to_filter = [
		'product'           => [
			'meta_query' => [
				[
					'key'     => self::KEY_VISIBILITY,
					'value'   => ChannelVisibility::SYNC_AND_SHOW,
					'compare' => '=',
				],
			],
		],
		'shop_coupon'       => [
			'meta_query' => [
				[
					'key'     => self::KEY_VISIBILITY,
					'value'   => ChannelVisibility::SYNC_AND_SHOW,
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
	 * Register all filters.
	 */
	public function register(): void {
		// Allow to filter by gla_syncable.
		add_filter(
			'woocommerce_rest_query_vars',
			function ( $valid_vars ) {
				$valid_vars[] = 'gla_syncable';
				return $valid_vars;
			}
		);

		$this->register_callbacks();

		foreach ( array_keys( self::$post_types_to_filter ) as $object_type ) {
			$this->register_object_types_filter( $object_type );
		}
	}

	/**
	 * Register the filters for a specific object type.
	 *
	 * @param string $object_type The object type.
	 */
	protected function register_object_types_filter( string $object_type ): void {
		add_filter(
			'woocommerce_rest_prepare_' . $object_type . '_object',
			[ $this, 'filter_response_by_syncable_item' ],
			PHP_INT_MAX, // Run this filter last to override any other response.
			3
		);

		add_filter(
			'woocommerce_rest_prepare_' . $object_type . '_object',
			[ $this, 'prepare_response' ],
			PHP_INT_MAX - 1,
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
	 * Register the callbacks.
	 */
	protected function register_callbacks() {
		add_filter(
			'rest_request_after_callbacks',
			/**
			 * Add the Google for WooCommerce and Ads settings to the settings/general response.
			 *
			 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response The response object.
			 * @param mixed                                             $handler  The handler.
			 * @param WP_REST_Request                                   $request  The request object.
			 */
			function ( $response, $handler, $request ) {
				if ( ! $this->is_gla_request( $request ) || ! $response instanceof WP_REST_Response ) {
					return $response;
				}

				$data = $response->get_data();

				if ( $request->get_route() === '/wc/v3/settings/general' ) {
					$data[] = [
						'id'    => 'gla_target_audience',
						'label' => 'Google for WooCommerce: Target Audience',
						'value' => $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] ),
					];

					$data[] = [
						'id'    => 'gla_shipping_times',
						'label' => 'Google for WooCommerce: Shipping Times',
						'value' => $this->shipping_time_query->get_all_shipping_times(),
					];

					$data[] = [
						'id'    => 'gla_language',
						'label' => 'Google for WooCommerce: Store language',
						'value' => get_locale(),
					];

					$response->set_data( array_values( $data ) );
				}

				$response->set_data( $this->prepare_data( $response->get_data(), $request ) );
				return $response;
			},
			10,
			3
		);
	}

	/**
	 * Prepares the data converting the empty arrays in objects for consistency.
	 *
	 * @param array           $data The response data to parse
	 * @param WP_REST_Request $request The request object.
	 * @return mixed
	 */
	public function prepare_data( $data, $request ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {
			if ( preg_match( '/^\/wc\/v3\/shipping\/zones\/\d+\/methods/', $request->get_route() ) && isset( $value['settings'] ) && empty( $value['settings'] ) ) {
				$data[ $key ]['settings'] = (object) $value['settings'];
			}
		}

		return $data;
	}

	/**
	 * Whether the request is coming from the WPCOM proxy.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool
	 */
	protected function is_gla_request( WP_REST_Request $request ): bool {
		// WPCOM proxy will set the gla_syncable to 1 if the request is coming from the proxy and it is the Google App.
		return $request->get_param( 'gla_syncable' ) === '1';
	}

	/**
	 * Get route pieces: resource and id, if present.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array The route pieces.
	 */
	protected function get_route_pieces( WP_REST_Request $request ): array {
		$route   = $request->get_route();
		$pattern = '/(?P<resource>[\w]+)(?:\/(?P<id>[\d]+))?$/';
		preg_match( $pattern, $route, $matches );

		return $matches;
	}

	/**
	 * Filter response by syncable item.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param mixed            $item     The item.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response The response object updated.
	 */
	public function filter_response_by_syncable_item( $response, $item, WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->is_gla_request( $request ) ) {
			return $response;
		}

		$pieces = $this->get_route_pieces( $request );

		if ( ! isset( $pieces['id'] ) || ! isset( $pieces['resource'] ) || ! in_array( $pieces['resource'], self::PROTECTED_RESOURCES, true ) ) {
			return $response;
		}

		$meta_data = $response->get_data()['meta_data'] ?? [];

		foreach ( $meta_data as $meta ) {
			if ( $meta->key === self::KEY_VISIBILITY && $meta->value === ChannelVisibility::SYNC_AND_SHOW ) {
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
	 * @param array           $args    The query args.
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array The query args updated.
	 * */
	public function filter_by_metaquery( array $args, WP_REST_Request $request ): array {
		if ( ! $this->is_gla_request( $request ) ) {
			return $args;
		}

		$post_type         = $args['post_type'];
		$post_type_filters = self::$post_types_to_filter[ $post_type ];

		if ( ! isset( $post_type_filters['meta_query'] ) || ! is_array( $post_type_filters['meta_query'] ) ) {
			return $args;
		}

		$args['meta_query'] = [ ...$args['meta_query'] ?? [], ...$post_type_filters['meta_query'] ];

		return $args;
	}

	/**
	 * Prepares the response when the request is coming from the WPCOM proxy:
	 *
	 * Filter all the private metadata and returns only the public metadata and those prefixed with _wc_gla
	 * For WooCommerce products, it will add the attribute mapping values.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param mixed            $item     The item.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response The response object updated.
	 */
	public function prepare_response( WP_REST_Response $response, $item, WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->is_gla_request( $request ) ) {
			return $response;
		}

		$data     = $response->get_data();
		$resource = $this->get_route_pieces( $request )['resource'] ?? null;

		if ( $item instanceof WC_Product && ( $resource === 'products' || $resource === 'variations' ) ) {
			$attr = $this->attribute_manager->get_all_aggregated_values( $item );
			// In case of empty array, convert to object to keep the response consistent.
			$data['gla_attributes'] = (object) $attr;

			// Force types and prevent user type change for fields as Google has strict type requirements.
			$data['price']         = strval( $data['price'] ?? null );
			$data['regular_price'] = strval( $data['regular_price'] ?? null );
			$data['sale_price']    = strval( $data['sale_price'] ?? null );
		}

		foreach ( $data['meta_data'] ?? [] as $key => $meta ) {
			if ( str_starts_with( $meta->key, '_' ) && ! str_starts_with( $meta->key, '_wc_gla' ) ) {
				unset( $data['meta_data'][ $key ] );
			}
		}

		$data['meta_data'] = array_values( $data['meta_data'] );

		$response->set_data( $data );

		return $response;
	}
}
