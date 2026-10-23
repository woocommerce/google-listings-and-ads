<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopStorePagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopStorePagesController
 *
 * ContainerAware used for:
 * - WP (in parent class)
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole
 */
class TopStorePagesController extends BaseReportsController {

	/** @var Connection */
	protected $connection;

	/** @var TopStorePagesService */
	protected $service;

	/**
	 * TopStorePagesController constructor.
	 *
	 * @param RESTServer           $server
	 * @param Connection           $connection
	 * @param TopStorePagesService $service
	 */
	public function __construct( RESTServer $server, Connection $connection, TopStorePagesService $service ) {
		parent::__construct( $server );

		$this->connection = $connection;
		$this->service    = $service;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'search-console/top-store-pages',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_top_store_pages_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the top store pages request.
	 *
	 * Available to every merchant, including service-based merchants, since a store without
	 * products still has pages.
	 *
	 * @return callable
	 */
	protected function get_top_store_pages_callback(): callable {
		return function ( Request $request ) {
			try {
				$property = $this->connection->get_connected_property();
				$args     = $this->prepare_query_arguments( $request );
				$limit    = $this->service->get_top_store_pages_limit();

				return $this->prepare_item_for_response(
					[
						'pages' => $this->service->get_top_store_pages( $property, $args['after'], $args['before'], $limit ),
						'limit' => $limit,
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
			'pages' => [
				'type'        => 'array',
				'description' => __( 'Top store pages that aren\'t products, ranked by clicks.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'post_id'     => [
							'type'        => [ 'integer', 'null' ],
							'description' => __( 'Post ID, or null for a page that isn\'t a post, such as an archive.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'name'        => [
							'type'        => 'string',
							'description' => __( 'Page name: the post title, or the URL path and query string for a page that isn\'t a post.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'url'         => [
							'type'        => 'string',
							'format'      => 'uri',
							'description' => __( 'Page URL.', 'google-listings-and-ads' ),
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
			'limit' => [
				'type'        => 'integer',
				'description' => __( 'Maximum number of pages the report returns.', 'google-listings-and-ads' ),
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
		return 'search_console_top_store_pages';
	}
}
