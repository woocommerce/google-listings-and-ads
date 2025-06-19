<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetRecommendations;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class BudgetRecommendationController
 *
 * ContainerAware used for:
 * - BudgetMetrics
 * - BudgetRecommendations
 * - BudgetRecommendationQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class BudgetRecommendationController extends BaseController implements ContainerAwareInterface, ISO3166AwareInterface {

	use ContainerAwareTrait;
	use CountryCodeTrait;

	/**
	 * @var Ads
	 */
	protected $ads;

	/**
	 * BudgetRecommendationController constructor.
	 *
	 * @param RESTServer $rest_server
	 * @param Ads        $ads
	 */
	public function __construct( RESTServer $rest_server, Ads $ads ) {
		parent::__construct( $rest_server );
		$this->ads = $ads;
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
				'minItems'          => 1,
			],
		];
	}

	/**
	 * @return callable
	 */
	protected function get_budget_recommendation_callback(): callable {
		return function ( Request $request ) {
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

			// Try to fetch recommendations from the Google Ads API.
			$budget_recommendations = $this->container->get( BudgetRecommendations::class );
			$recommendations        = $budget_recommendations->get_recommendations( $country_codes );

			// For the frontend side to track the source of the recommendations.
			$source = 'google-ads-api';

			// The fallback recommendation is still needed to ensure there is a
			// baseline budget for validating the minimum value.
			$budget_baseline = 0;

			// Fetch fallback recommendation from the database.
			$fallback_recommendation = $this->get_fallback_recommendation( $country_codes, $currency );
			if ( $fallback_recommendation ) {
				$fallback_budget = $fallback_recommendation[0]['daily_budget'] ?? 0;
				$budget_baseline = $fallback_budget;

				// Swap recommended if not set or if fallback is higher.
				if ( empty( $recommendations[0] ) || ( ! empty( $recommendations[0]['daily_budget'] ) && $recommendations[0]['daily_budget'] < $fallback_budget ) ) {
					$recommendations[0] = $fallback_recommendation[0];
					$source             = 'fallback-database';

					// Fetch metrics from the API for the fallback budget (only when we are going to use the fallback).
					$budget_metrics   = $this->container->get( BudgetMetrics::class );
					$fallback_metrics = $budget_metrics->get_metrics( $fallback_budget, $country_codes );
					if ( $fallback_metrics ) {
						$recommendations[0]['metrics'] = $fallback_metrics;
					}

					// Remove high recommendation if fallback is higher.
					if ( ! empty( $recommendations[1]['daily_budget'] ) && $recommendations[1]['daily_budget'] < $fallback_budget ) {
						unset( $recommendations[1] );
					}

					// Remove low recommendation if fallback is higher.
					if ( ! empty( $recommendations[2]['daily_budget'] ) && $recommendations[2]['daily_budget'] < $fallback_budget ) {
						unset( $recommendations[2] );
					}
				}
			}

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

			return $this->prepare_item_for_response(
				[
					'currency'              => $currency,
					'recommendations'       => $recommendations,
					'daily_budget_baseline' => $budget_baseline,
					'source'                => $source,
				],
				$request
			);
		};
	}

	/**
	 * Returns a fallback recommendation from the database for the primary country (first in the list).
	 *
	 * @param array  $country_codes List of countries to include.
	 * @param string $currency      Currency to use for recommendations.
	 *
	 * @return array|null Recommendation for the primary country.
	 */
	protected function get_fallback_recommendation( array $country_codes, string $currency ): ?array {
		$query           = $this->container->get( BudgetRecommendationQuery::class );
		$primary_country = reset( $country_codes );
		$recommendations = $query
			->where( 'country', $primary_country )
			->where( 'currency', $currency )
			->get_results();

		if ( ! $recommendations ) {
			return null;
		}

		return array_map(
			function ( $recommendation ) {
				return [
					'daily_budget' => (float) $recommendation['daily_budget'],
					'country'      => $recommendation['country'],
					'level'        => __( 'Recommended', 'google-listings-and-ads' ),
				];
			},
			$recommendations
		);
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'currency'              => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'recommendations'       => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'country'      => [
							'type'        => 'string',
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'daily_budget' => [
							'type'        => 'number',
							'description' => __( 'The recommended daily budget for a country.', 'google-listings-and-ads' ),
						],
						'level'        => [
							'type'        => 'string',
							'description' => __( 'Label for the recommendation level: High, Recommended, Low', 'google-listings-and-ads' ),
						],
						'metrics'      => [
							'type'       => 'object',
							'properties' => [
								'cost'              => [
									'type'        => 'number',
									'description' => __( 'Estimated average amount you will spend weekly during the month.', 'google-listings-and-ads' ),
									'context'     => [ 'view' ],
								],
								'conversions'       => [
									'type'        => 'number',
									'description' => __( 'Estimated number of conversions (unit sales) for a typical week.', 'google-listings-and-ads' ),
									'context'     => [ 'view' ],
								],
								'conversions_value' => [
									'type'        => 'number',
									'description' => __( 'Estimated total value of all the conversions (sales volume) your campaign will generate in a week.', 'google-listings-and-ads' ),
									'context'     => [ 'view' ],
								],
							],
						],
					],
				],
			],
			'daily_budget_baseline' => [
				'type'        => 'number',
				'description' => __( 'The baseline daily budget for a country.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'source'                => [
				'type'        => 'string',
				'enum'        => [ 'google-ads-api', 'fallback-database' ],
				'description' => __( 'Data source of the budget recommendations, either from Google Ads API or fallback database.', 'google-listings-and-ads' ),
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
		return 'budget-recommendation';
	}
}
