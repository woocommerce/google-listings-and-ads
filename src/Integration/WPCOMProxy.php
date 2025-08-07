<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use WC_Product;
use WP_REST_Response;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPCOMProxy
 *
 * Prepares and filters data pulled by WPCOM proxy based on the `gla_syncable` query parameter.
 *
 * The `gla_syncable` parameter indicates that the request originates from the WPCOM proxy.
 * It's not intended to provide security, as it does not expose any data
 * that should be hidden from REST API users.
 *
 * Its primary purpose is to prevent global endpoints from being cluttered with additional data
 * and to conceal undocumented implementation details of the integration between the G4W plugin and WPCOM proxy.
 *
 * @since 2.8.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WPCOMProxy implements Service, Registerable, OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The ShippingRateQuery object.
	 *
	 * @var ShippingRateQuery
	 */
	protected $shipping_rate_query;

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
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * The protected resources. Only items with visibility set to sync-and-show will be returned.
	 */
	protected const PROTECTED_RESOURCES = [
		'products',
		'coupons',
	];

	/**
	 * The settings group for the REST API.
	 *
	 * @var string
	 */
	protected const SETTINGS_GROUP = 'google-for-woocommerce';

	/**
	 * WPCOMProxy constructor.
	 *
	 * @param ShippingRateQuery $shipping_rate_query The ShippingRateQuery object.
	 * @param ShippingTimeQuery $shipping_time_query The ShippingTimeQuery object.
	 * @param AttributeManager  $attribute_manager   The AttributeManager object.
	 * @param ProductRepository $product_repository  The ProductRepository object.
	 */
	public function __construct( ShippingRateQuery $shipping_rate_query, ShippingTimeQuery $shipping_time_query, AttributeManager $attribute_manager, ProductRepository $product_repository ) {
		$this->shipping_rate_query = $shipping_rate_query;
		$this->shipping_time_query = $shipping_time_query;
		$this->attribute_manager   = $attribute_manager;
		$this->product_repository  = $product_repository;
	}

	/**
	 * The meta key used to filter the items.
	 *
	 * @var string
	 */
	public const KEY_VISIBILITY = '_wc_gla_visibility';

	/**
	 * Get the post types to be filtered.
	 *
	 * @return array
	 */
	private function get_post_types_to_filter() {
		return [
			'product'           => [
				'meta_query' => $this->product_repository->get_sync_ready_products_meta_query( true ),
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
	}

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
		$this->add_g4w_settings();

		foreach ( array_keys( $this->get_post_types_to_filter() ) as $object_type ) {
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
	 * Register the `rest_request_after_callbacks`, to prepare shipping methods.
	 */
	protected function register_callbacks() {
		add_filter(
			'rest_request_after_callbacks',
			/**
			 * Set data for settings & prepare data for shipping methods.
			 *
			 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response The response object.
			 * @param mixed                                             $handler  The handler.
			 * @param WP_REST_Request                                   $request  The request object.
			 */
			function ( $response, $handler, $request ) {
				if ( ! $this->is_gla_request( $request ) || ! $response instanceof WP_REST_Response ) {
					return $response;
				}

				if ( $request->get_route() === '/wc/v3/settings/' . self::SETTINGS_GROUP ) {

					$data = $response->get_data();

					$data[] = [
						'id'    => 'gla_google_connected',
						'label' => 'Google for WooCommerce: Is Google account connected?',
						'value' => rest_sanitize_boolean( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) ),
					];
					$data[] = [
						'id'    => 'gla_language',
						'label' => 'Google for WooCommerce: Store language',
						'value' => get_locale(),
					];
					$data[] = [
						'id'    => 'gla_merchant_center',
						'label' => 'Google for WooCommerce: Merchant Center settings',
						'value' => $this->options->get( OptionsInterface::MERCHANT_CENTER, null ),
					];
					$data[] = [
						'id'    => 'gla_shipping_rates',
						'label' => 'Google for WooCommerce: Shipping Rates',
						'value' => (object) $this->shipping_rate_query->get_all_shipping_rates(),
					];
					$data[] = [
						'id'    => 'gla_shipping_times',
						'label' => 'Google for WooCommerce: Shipping Times',
						'value' => (object) $this->shipping_time_query->get_all_shipping_times(),
					];
					$data[] = [
						'id'    => 'gla_target_audience',
						'label' => 'Google for WooCommerce: Target Audience',
						'value' => $this->options->get( OptionsInterface::TARGET_AUDIENCE, null ),
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
	 * Add the Google for WooCommerce settings to the WooCommerce REST API.
	 *
	 * @return void
	 */
	protected function add_g4w_settings() {
		add_filter(
			'woocommerce_settings_groups',
			function ( $locations ) {
				$locations[] = [
					'id'          => self::SETTINGS_GROUP,
					'label'       => 'Google for WooCommerce',
					'description' => 'Settings of the Google for WooCommerce plugin.',
				];
				return $locations;
			}
		);

		// Hack to make the settings group show up in the response.
		add_filter(
			'woocommerce_settings-' . self::SETTINGS_GROUP,
			function ( $data ) {
				/*
				 * We need to add non-empty return value in the filter, to be able to pass the valid group check.
				 * We provide invalid 'type', so the entry will be ignored
				 * in the response by `WC_REST_Setting_Options_V2_Controller::get_items`
				 */
				$data[] = [
					'id'         => 'gla_settings_placeholder',
					'option_key' => 'gla_settings_placeholder',
					'type'       => '_invalid_type_',
				];

				/*
				 * This way `rest_request_after_callbacks` will get an empty data set
				 * and could provide complete option- and non-option related settings.
				 */
				return $data;
			}
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

		if ( preg_match( '/^\/wc\/v3\/shipping\/zones\/\d+\/methods/', $request->get_route() ) ) {
			foreach ( $data as $key => $value ) {
				if ( isset( $value['settings'] ) && empty( $value['settings'] ) ) {
					$data[ $key ]['settings'] = (object) $value['settings'];
				}
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

		// Product is opt-out but coupon is opt-in
		$is_syncable = $pieces['resource'] === 'products';
		$meta_data   = $response->get_data()['meta_data'] ?? [];

		foreach ( $meta_data as $meta ) {
			if ( $meta->key === self::KEY_VISIBILITY ) {
				$is_syncable = $meta->value === ChannelVisibility::SYNC_AND_SHOW;
				break;
			}
		}

		if ( $is_syncable ) {
			return $response;
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
		$post_type_filters = $this->get_post_types_to_filter()[ $post_type ];

		if ( ! isset( $post_type_filters['meta_query'] ) || ! is_array( $post_type_filters['meta_query'] ) ) {
			return $args;
		}

		$meta_query = $post_type_filters['meta_query'];

		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = $meta_query;
		} else {
			array_push( $args['meta_query'], $meta_query );
		}

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
