<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchAnalyticsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsController
 *
 * ContainerAware used for:
 * - WP (in parent class)
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole
 */
class ReportsController extends BaseReportsController {

	/** @var Connection */
	protected $connection;

	/** @var SearchAnalyticsService */
	protected $search_analytics;

	/**
	 * ReportsController constructor.
	 *
	 * @param RESTServer             $server
	 * @param Connection             $connection
	 * @param SearchAnalyticsService $search_analytics
	 */
	public function __construct( RESTServer $server, Connection $connection, SearchAnalyticsService $search_analytics ) {
		parent::__construct( $server );

		$this->connection       = $connection;
		$this->search_analytics = $search_analytics;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'search-console/reports/queries',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_queries_report_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the top queries report request.
	 *
	 * @return callable
	 */
	protected function get_queries_report_callback(): callable {
		return function ( Request $request ) {
			try {
				$property = $this->get_connected_property();
				$args     = $this->prepare_query_arguments( $request );
				$limit    = $this->search_analytics->get_top_queries_limit();

				return $this->prepare_item_for_response(
					[
						'queries' => $this->search_analytics->get_top_queries( $property, $args['after'], $args['before'], $limit ),
						'limit'   => $limit,
					],
					$request
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the stored property, only when it is selected and verified.
	 *
	 * Reads the stored connection data rather than resolving the connection status, so the
	 * report request itself never triggers property resolution or revalidation.
	 *
	 * @return string
	 * @throws Exception When Search Console isn't connected to a verified property.
	 */
	protected function get_connected_property(): string {
		$data = $this->connection->get_connection_data();

		if ( empty( $data['property'] ) || SiteVerification::VERIFICATION_STATUS_VERIFIED !== ( $data['verified'] ?? null ) ) {
			throw new Exception( __( 'Search Console is not connected to a verified property.', 'google-listings-and-ads' ), 400 );
		}

		return (string) $data['property'];
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
			'queries' => [
				'type'        => 'array',
				'description' => __( 'Top search queries, ranked by clicks.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'query'       => [
							'type'        => 'string',
							'description' => __( 'Search query.', 'google-listings-and-ads' ),
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
			'limit'   => [
				'type'        => 'integer',
				'description' => __( 'Maximum number of queries the report returns.', 'google-listings-and-ads' ),
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
		return 'search_console_reports';
	}
}
