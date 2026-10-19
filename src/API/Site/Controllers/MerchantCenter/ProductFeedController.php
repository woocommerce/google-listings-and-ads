<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductFeedQueryHelper;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\ProductJourney;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFeedController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ProductFeedController extends BaseController {

	/**
	 * @var ProductFeedQueryHelper
	 */
	protected $query_helper;

	/**
	 * ProductFeedController constructor.
	 *
	 * @param RESTServer             $server
	 * @param ProductFeedQueryHelper $query_helper
	 */
	public function __construct( RESTServer $server, ProductFeedQueryHelper $query_helper ) {
		parent::__construct( $server );
		$this->query_helper = $query_helper;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/product-feed',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_product_feed_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
					// Only the status filters are validated by the route: registering every
					// collection param would start enforcing rules the endpoint never applied
					// (e.g. the orderby enum lacks total_sales, and ids would become an array).
					'args'                => array_intersect_key(
						$this->get_collection_params(),
						array_flip( [ 'journey', 'visibility' ] )
					),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Get the callback function for returning the product feed.
	 *
	 * @return callable
	 */
	protected function get_product_feed_read_callback(): callable {
		return function ( Request $request ) {
			try {
				return [
					'products' => $this->query_helper->get( $request ),
					'total'    => $this->query_helper->count( $request ),
					'page'     => $request['per_page'] > 0 && $request['page'] > 0 ? $request['page'] : 1,
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'products' => [
				'type'        => 'array',
				'description' => __( 'The store\'s products.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'                => [
							'type'        => 'numeric',
							'description' => __( 'Product ID.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'title'             => [
							'type'        => 'string',
							'description' => __( 'Product title.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'visible'           => [
							'type'        => 'boolean',
							'description' => __( 'Whether the product is set to be visible in the Merchant Center', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'status'            => [
							'type'        => 'string',
							'description' => __( 'The current sync status of the product.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'image_url'         => [
							'type'        => 'string',
							'description' => __( 'The image url of the product.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'price'             => [
							'type'        => 'string',
							'description' => __( 'The price of the product.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'errors'            => [
							'type'        => 'array',
							'description' => __( 'Errors preventing the product from being synced to the Merchant Center.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'catalog_visible'   => [
							'type'        => 'boolean',
							'description' => __( 'Whether the product is visible in the store catalog. Hidden products are not synced, whatever their channel visibility.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'sync_status_group' => [
							'type'        => 'string',
							'description' => __( 'The product\'s sync status, matching the status tab it is listed under.', 'google-listings-and-ads' ),
							'enum'        => ProductJourney::SEGMENTS,
							'context'     => [ 'view' ],
						],
						'issues'            => [
							'type'        => 'array',
							'description' => __( 'Merchant Center issues for the product (and its variations), followed by its local sync errors.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'issue'      => [
										'type'        => 'string',
										'description' => __( 'Issue description.', 'google-listings-and-ads' ),
									],
									'code'       => [
										'type'        => 'string',
										'description' => __( 'Issue code.', 'google-listings-and-ads' ),
									],
									'action'     => [
										'type'        => 'string',
										'description' => __( 'How to fix the issue.', 'google-listings-and-ads' ),
									],
									'action_url' => [
										'type'        => 'string',
										'description' => __( 'Link with more information about the fix.', 'google-listings-and-ads' ),
									],
									'severity'   => [
										'type'        => 'string',
										'description' => __( 'Issue severity.', 'google-listings-and-ads' ),
										'enum'        => [ 'error', 'warning' ],
									],
									'source'     => [
										'type'        => 'string',
										'description' => __( 'Where the issue was reported: by Merchant Center, or when the product was sent.', 'google-listings-and-ads' ),
										'enum'        => [ 'merchant_center', 'sync' ],
									],
								],
							],
						],
					],
				],
			],
			'total'    => [
				'type'     => 'numeric',
				'context'  => [ 'view' ],
				'readonly' => true,
			],
			'page'     => [
				'type'     => 'numeric',
				'context'  => [ 'view' ],
				'readonly' => true,
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'product_feed';
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'context'    => $this->get_context_param( [ 'default' => 'view' ] ),
			'page'       => [
				'description'       => __( 'Page of data to retrieve.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page'   => [
				'description'       => __( 'Maximum number of rows to be returned in result data.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 0,
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'search'     => [
				'description'       => __( 'Text to search for in product names.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'ids'        => [
				'description'       => __( 'Limit result to items with specified ids (comma-separated).', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'integer',
				],
			],
			'journey'    => [
				'description'       => __( 'Limit result to products with this sync status.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'enum'              => ProductJourney::SEGMENTS,
				'validate_callback' => 'rest_validate_request_arg',
			],
			'visibility' => [
				'description'       => __( 'Limit result to products with this channel visibility. Products without a visibility setting count as "sync and show"; products hidden from the store catalog count as "don\'t sync and show".', 'google-listings-and-ads' ),
				'type'              => 'string',
				'enum'              => ChannelVisibility::ALLOWED_VALUES,
				'validate_callback' => 'rest_validate_request_arg',
			],
			'orderby'    => [
				'description'       => __( 'Sort collection by attribute.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => 'title',
				'enum'              => [ 'title', 'id', 'visible', 'status' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'order'      => [
				'description'       => __( 'Order sort attribute ascending or descending.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => 'ASC',
				'enum'              => [ 'ASC', 'DESC' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
