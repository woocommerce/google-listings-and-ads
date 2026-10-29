<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopProductPagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopProductPagesController
 *
 * ContainerAware used for:
 * - WP (in parent class)
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole
 */
class TopProductPagesController extends BaseReportsController {

	/** @var Connection */
	protected $connection;

	/** @var TopProductPagesService */
	protected $service;

	/** @var MerchantCenterService */
	protected $merchant_center;

	/**
	 * TopProductPagesController constructor.
	 *
	 * @param RESTServer             $server
	 * @param Connection             $connection
	 * @param TopProductPagesService $service
	 * @param MerchantCenterService  $merchant_center
	 */
	public function __construct( RESTServer $server, Connection $connection, TopProductPagesService $service, MerchantCenterService $merchant_center ) {
		parent::__construct( $server );

		$this->connection      = $connection;
		$this->service         = $service;
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'search-console/top-product-pages',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_top_product_pages_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the top product pages request.
	 *
	 * Product pages are matched to the store's products, so the data is only available once Merchant Center is connected.
	 *
	 * @return callable
	 */
	protected function get_top_product_pages_callback(): callable {
		return function ( Request $request ) {
			if ( ! $this->merchant_center->is_connected() ) {
				return new Response(
					[ 'message' => __( 'Top product pages are only available when Merchant Center is connected.', 'google-listings-and-ads' ) ],
					404
				);
			}

			try {
				$property = $this->connection->get_connected_property();
				$args     = $this->prepare_query_arguments( $request );
				$limit    = $this->service->get_top_product_pages_limit();

				return $this->prepare_item_for_response(
					[
						'products' => $this->service->get_top_product_pages( $property, $args['after'], $args['before'], $limit ),
						'limit'    => $limit,
					],
					$request
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return array_intersect_key(
			parent::get_collection_params(),
			array_flip( [ 'context', 'after', 'before' ] )
		);
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'products' => [
				'type'        => 'array',
				'description' => __( 'Top products, ranked by clicks on their pages.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'product_id'  => [
							'type'        => 'integer',
							'description' => __( 'Product ID.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'name'        => [
							'type'        => 'string',
							'description' => __( 'Product name.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'clicks'      => [
							'type'        => 'integer',
							'description' => __( 'Clicks.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'impressions' => [
							'type'        => 'integer',
							'description' => __( 'Impressions.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'ctr'         => [
							'type'        => 'number',
							'description' => __( 'Click-through rate, as a fraction between 0 and 1.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'position'    => [
							'type'        => 'number',
							'description' => __( 'Average position.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
					],
				],
			],
			'limit'    => [
				'type'        => 'integer',
				'description' => __( 'Maximum number of products the report returns.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
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
		return 'search_console_top_product_pages';
	}
}
