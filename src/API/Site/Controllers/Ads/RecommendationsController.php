<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class RecommendationsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class RecommendationsController extends BaseController {

	/**
	 * Service used to access / update Ads account data.
	 *
	 * @var AccountService
	 */
	protected $account;

	/**
	 * RecommendationsController constructor.
	 *
	 * @param RESTServer     $server
	 * @param AccountService $account
	 */
	public function __construct( RESTServer $server, AccountService $account ) {
		parent::__construct( $server );
		$this->account = $account;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/recommendations',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_recommendations_callback(),
					//'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'type' => [
				'type'        => 'string',
				'description' => __( 'Filter recommendations by type', 'google-listings-and-ads' ),
				// This could also use a callback to get the set of supported recommendation types from the `AdsRecommendations` service.
				'enum'        => array( 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH' ),
				'required'    => false,
			],
			'id'   => [
				'type'        => 'integer',
				'description' => __( 'Filter recommendations by unique id', 'google-listings-and-ads' ),
				'required'    => false,
			],
		];
	}

	/**
	 * Get the callback function for the list accounts request.
	 *
	 * @return callable
	 */
	protected function get_recommendations_callback(): callable {
		return function ( Request $request ) {
			try {
				// Checks if the ads account is connected; exits early if not connected to prevent further execution.
				$account_status = $this->account->get_connected_account();
				if ( isset( $account_status['status'] ) && 'connected' !== $account_status['status'] ) {
					return new Response(
						[ 'message' => __( 'No connected Ads account found.', 'google-listings-and-ads' ) ],
						403
					);
				}

				$type = $request->get_param( 'type' );

				// TODO: Replace this with real data from AdsRecommendations service.
				$recommendations = [
					[
						'id'              => 1,
						'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
						'resource_name'   => 'customers/123/recommendations/1',
						'campaign_id'     => 100,
						'campaign_name'   => 'Test Campaign',
						'campaign_status' => 'ENABLED',
						'last_synced'     => gmdate( 'c' ),
					],
					[
						'id'              => 2,
						'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
						'resource_name'   => 'customers/123/recommendations/2',
						'campaign_id'     => 101,
						'campaign_name'   => 'Another Campaign',
						'campaign_status' => 'PAUSED',
						'last_synced'     => gmdate( 'c' ),
					],
				];

				// If type is set, filter by type (only IMPROVE_PERFORMANCE_MAX_AD_STRENGTH is supported).
				if ( $type ) {
					$recommendations = array_filter(
						$recommendations,
						static function ( $rec ) use ( $type ) {
							return $rec['type'] === $type;
						}
					);
				}

				// Filter by id if provided.
				$id = $request->get_param( 'id' );
				if ( $id ) {
					$recommendations = array_filter(
						$recommendations,
						static function ( $rec ) use ( $id ) {
							return (int) $rec['id'] === (int) $id;
						}
					);
				}

				// Prepare each recommendation for response using the schema.
				$response_data = [];
				foreach ( $recommendations as $recommendation ) {
					$response_data[] = $this->prepare_item_for_response( $recommendation, $request );
				}

				return new Response( array_values( $response_data ) );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'              => [
				'type'        => 'integer',
				'description' => __( 'Recommendation ID.', 'google-listings-and-ads' ),
			],
			'type'            => [
				'type'        => 'string',
				'description' => __( 'Recommendation type.', 'google-listings-and-ads' ),
			],
			'resource_name'   => [
				'type'        => 'string',
				'description' => __( 'Resource name of the recommendation.', 'google-listings-and-ads' ),
			],
			'campaign_id'     => [
				'type'        => 'integer',
				'description' => __( 'Campaign ID associated with the recommendation.', 'google-listings-and-ads' ),
			],
			'campaign_name'   => [
				'type'        => 'string',
				'description' => __( 'Campaign name associated with the recommendation.', 'google-listings-and-ads' ),
			],
			'campaign_status' => [
				'type'        => 'string',
				'description' => __( 'Status of the campaign.', 'google-listings-and-ads' ),
				'enum'        => [ 'ENABLED', 'PAUSED', 'REMOVED', 'UNKNOWN', 'UNSPECIFIED' ],
			],
			'last_synced'     => [
				'type'        => 'string',
				'format'      => 'date-time',
				'description' => __( 'Last synced date and time.', 'google-listings-and-ads' ),
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
		return 'recommendations';
	}
}
