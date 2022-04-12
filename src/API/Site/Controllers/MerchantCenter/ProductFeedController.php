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
		return function( Request $request ) {
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
						'id'      => [
							'type'        => 'numeric',
							'description' => __( 'Product ID.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'title'   => [
							'type'        => 'string',
							'description' => __( 'Product title.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'visible' => [
							'type'        => 'boolean',
							'description' => __( 'Whether the product is set to be visible in the Merchant Center', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'status'  => [
							'type'        => 'string',
							'description' => __( 'The current sync status of the product.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'errors'  => [
							'type'        => 'array',
							'description' => __( 'Errors preventing the product from being synced to the Merchant Center.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
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
			'context'  => $this->get_context_param( [ 'default' => 'view' ] ),
			'page'     => [
				'description'       => __( 'Page of data to retrieve.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page' => [
				'description'       => __( 'Maximum number of rows to be returned in result data.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 0,
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'search'   => [
				'description'       => __( 'Text to search for in product names.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'ids'      => [
				'description'       => __( 'Limit result to items with specified ids (comma-separated).', 'google-listings-and-ads' ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_list',
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'integer',
				],
			],
			'orderby'  => [
				'description'       => __( 'Sort collection by attribute.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => 'title',
				'enum'              => [ 'title', 'id', 'visible', 'status' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'order'    => [
				'description'       => __( 'Order sort attribute ascending or descending.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => 'ASC',
				'enum'              => [ 'ASC', 'DESC' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
