<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class BudgetMetricsController
 *
 * ContainerAware used for:
 * - BudgetMetrics
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class BudgetMetricsController extends BaseController implements ContainerAwareInterface, ISO3166AwareInterface {

	use ContainerAwareTrait;
	use CountryCodeTrait;

	/**
	 * @var Ads
	 */
	protected $ads;

	/**
	 * BudgetMetricsController constructor.
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
			'ads/campaigns/budget-metrics',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_budget_metrics_callback(),
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
			'budget'        => [
				'description'       => __( 'Budget to fetch metrics for.', 'google-listings-and-ads' ),
				'type'              => 'number',
				'minimum'           => 0,
				'sanitize_callback' => $this->get_sanitize_price_callback(),
				'validate_callback' => 'rest_validate_request_arg',
			],
			'country_codes' => [
				'description'       => __( 'List of country codes to fetch metrics for.', 'google-listings-and-ads' ),
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
	protected function get_budget_metrics_callback(): callable {
		return function ( Request $request ) {
			$country_codes = $request->get_param( 'country_codes' );
			$budget        = $request->get_param( 'budget' );
			$currency      = $this->ads->get_ads_currency();
			$country       = reset( $country_codes );

			if ( ! $currency ) {
				return new Response(
					[
						'message'       => __( 'No currency available for the Ads account.', 'google-listings-and-ads' ),
						'budget'        => $budget,
						'currency'      => $currency,
						'country_codes' => $country_codes,
					],
					400
				);
			}

			// Fetch metrics from the Google Ads API.
			$budget_metrics = $this->container->get( BudgetMetrics::class );
			$metrics        = $budget_metrics->get_metrics( $budget, $country_codes );

			if ( ! $metrics ) {
				return new Response(
					[
						'message'       => __( 'Cannot find any budget metrics.', 'google-listings-and-ads' ),
						'budget'        => $budget,
						'currency'      => $currency,
						'country_codes' => $country_codes,
					],
					404
				);
			}

			return $this->prepare_item_for_response(
				[
					'currency' => $currency,
					'budget'   => $budget,
					'country'  => $country,
					'metrics'  => $metrics,
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
			'currency' => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the metrics.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'budget'   => [
				'type'              => 'number',
				'description'       => __( 'Budget we requested metrics for.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'country'  => [
				'type'        => 'string',
				'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'metrics'  => [
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
		return 'budget-metrics';
	}
}
