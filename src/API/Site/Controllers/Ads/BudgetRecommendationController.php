<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class BudgetRecommendationController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class BudgetRecommendationController extends BaseController implements ISO3166AwareInterface {

	use CountryCodeTrait;

	/**
	 * @var BudgetRecommendationQuery
	 */
	protected $budget_recommendation_query;

	/**
	 * @var Ads
	 */
	protected $ads;

	/**
	 * BudgetRecommendationController constructor.
	 *
	 * @param RESTServer                $rest_server
	 * @param BudgetRecommendationQuery $budget_recommendation_query
	 * @param Ads                       $ads
	 */
	public function __construct( RESTServer $rest_server, BudgetRecommendationQuery $budget_recommendation_query, Ads $ads ) {
		parent::__construct( $rest_server );
		$this->budget_recommendation_query = $budget_recommendation_query;
		$this->ads                         = $ads;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/campaigns/budget-recommendation',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_budget_recommendation_callback(),
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
			'context'       => $this->get_context_param( [ 'default' => 'view' ] ),
			'country_codes' => [
				'type'              => 'array',
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'items'             => [
					'type' => 'string',
				],
				'required'          => true,
			],
		];
	}

	/**
	 * @return callable
	 */
	protected function get_budget_recommendation_callback(): callable {
		return function( Request $request ) {
			$country_codes = $request->get_param( 'country_codes' );
			$currency      = $this->ads->get_ads_currency();

			if ( ! $currency ) {
				return new Response(
					[
						'message'       => __( 'No currency available for the Ads account.', 'google-listings-and-ads' ),
						'currency'      => $currency,
						'country_codes' => $country_codes,
					],
					400
				);
			}

			$recommendations = $this
				->budget_recommendation_query
				->where( 'country', $country_codes, 'IN' )
				->where( 'currency', $currency )
				->get_results();

			if ( ! $recommendations ) {
				return new Response(
					[
						'message'       => __( 'Cannot find any budget recommendations.', 'google-listings-and-ads' ),
						'currency'      => $currency,
						'country_codes' => $country_codes,
					],
					404
				);
			}

			$returned_recommendations = array_map(
				function ( $recommendation ) {
					return [
						'country'           => $recommendation['country'],
						'daily_budget_low'  => (int) $recommendation['daily_budget_low'],
						'daily_budget_high' => (int) $recommendation['daily_budget_high'],
					];
				},
				$recommendations
			);

			return $this->prepare_item_for_response(
				[
					'currency'        => $currency,
					'recommendations' => $returned_recommendations,
				],
				$request
			);
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'currency'        => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'recommendations' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'country'           => [
							'type'        => 'string',
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'daily_budget_low'  => [
							'type'        => 'number',
							'description' => __( 'The lower bound recommended daily budget for a country.', 'google-listings-and-ads' ),
						],
						'daily_budget_high' => [
							'type'        => 'number',
							'description' => __( 'The upper bound recommended daily budget for a country.', 'google-listings-and-ads' ),
						],
					],
				],
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
		return 'budget-recommendation';
	}
}
