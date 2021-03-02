<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use Psr\Container\ContainerInterface;
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
	 * BaseController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->budget_recommendation_query = $container->get( BudgetRecommendationQuery::class );
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/campaigns/budget-recommendation/(?P<country_code>\\w{2})',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_budget_recommendation_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}
	/**
	 * @return callable
	 */
	protected function get_budget_recommendation_callback(): callable {
		return function( Request $request ) {
			$country        = $request->get_param( 'country_code' );
			$currency       = get_option( 'woocommerce_currency' );
			$recommendation = $this
				->budget_recommendation_query
				->where( 'country', $country )
				->where( 'currency', $currency )
				->get_results();

			if ( ! $recommendation || 1 !== count( $recommendation ) ) {
				return new Response(
					[
						'message'      => __( 'Invalid country/currency combination', 'google-listings-and-ads' ),
						'country_code' => $country,
						'currency'     => $currency,
					],
					400
				);
			}

			return [
				'country_code'      => $recommendation[0]['country'],
				'currency'          => $recommendation[0]['currency'],
				'daily_budget_low'  => $recommendation[0]['daily_budget_low'],
				'daily_budget_high' => $recommendation[0]['daily_budget_high'],
			];
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'      => [
				'type'        => 'integer',
				'description' => __( 'ID number.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'name'    => [
				'type'              => 'string',
				'description'       => __( 'Descriptive campaign name.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'status'  => [
				'type'              => 'string',
				'enum'              => CampaignStatus::labels(),
				'description'       => __( 'Campaign status.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'amount'  => [
				'type'              => 'number',
				'description'       => __( 'Budget amount in the local currency.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'country' => [
				'type'              => 'string',
				'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'required'          => true,
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
		return 'campaign';
	}
}
