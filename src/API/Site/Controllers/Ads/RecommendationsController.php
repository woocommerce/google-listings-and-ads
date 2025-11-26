<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
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
class RecommendationsController extends BaseController implements ContainerAwareInterface {

	use ContainerAwareTrait;

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
					'permission_callback' => $this->get_permission_callback(),
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
			'types'       => [
				'type'        => 'array',
				'description' => __( 'Filter recommendations by one or more types', 'google-listings-and-ads' ),
				'items'       => [
					'type' => 'string',
					'enum' => AdsRecommendationsService::VALID_RECOMMENDATION_TYPES,
				],
				'required'    => true,
			],
			'campaign_id' => [
				'type'        => 'integer',
				'description' => __( 'Filter recommendations by campaign id', 'google-listings-and-ads' ),
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
				/** @var AdsRecommendationsService $query */
				$query = $this->container->get( AdsRecommendationsService::class );

				$types = $request->get_param( 'types' ) ?? [];
				if ( is_string( $types ) ) {
					$types = array_map( 'trim', explode( ',', $types ) );
				}

				// Filter $type to only allow valid recommendation types.
				$types       = AdsRecommendationsService::get_valid_recommendation_types( $types );
				$campaign_id = (int) $request->get_param( 'campaign_id' );

				$args = [
					'types'       => $types,
					'campaign_id' => $campaign_id,
				];

				$recommendations = $query->get_recommendations( $args );

				$result = [];
				foreach ( $recommendations as $recommendation ) {
					$data     = $this->prepare_item_for_response( $recommendation, $request );
					$result[] = $this->prepare_response_for_collection( $data );
				}

				return new Response( $result );
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
			'details'         => [
				'type'        => 'array',
				'description' => __( 'Additional details related to the recommendation', 'google-listings-and-ads' ),
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
