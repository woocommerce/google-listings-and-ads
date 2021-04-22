<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WP_REST_Request as Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFeedController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ProductFeedController extends BaseOptionsController {

	/**
	 * @var ProductRepository
	 */
	protected $repo;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * ProductFeedController constructor.
	 *
	 * @param RESTServer         $server
	 * @param ProductRepository  $repo
	 * @param ProductMetaHandler $meta_handler
	 */
	public function __construct( RESTServer $server, ProductRepository $repo, ProductMetaHandler $meta_handler ) {
		parent::__construct( $server );
		$this->repo         = $repo;
		$this->meta_handler = $meta_handler;
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
			$products = [];

			$args = [
				'type' => [ 'simple', 'variable' ],
			];

			foreach ( $this->repo->find( $args ) as $product ) {

				$id     = $product->get_id();
				$status = 'not-synced';
				if ( $this->meta_handler->get_synced_at( $id ) ) {
					$status = 'synced';
				} elseif ( empty( $this->meta_handler->get_errors( $id ) ) ) {
					$status = 'pending';
				}

				$products[ $id ] = [
					'id'      => $id,
					'title'   => $product->get_name(),
					'visible' => $this->meta_handler->get_visibility( $id ) !== ChannelVisibility::DONT_SYNC_AND_SHOW,
					'status'  => $status,
					'errors'  => $this->meta_handler->get_errors( $id ),
				];
			}
			krsort( $products );
			return [ 'products' => array_values( $products ) ];
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [];
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
			'query'    => [
				'description'       => __( 'Text to search for in product names.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'order_by' => [
				'description'       => __( 'Column by which to sort the data.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'order'    => [
				'description'       => __( 'Direction to sort the data.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
