<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ShippingRateSchemaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingSuggestionService;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateSuggestionsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 *
 * @since 1.12.0
 */
class ShippingRateSuggestionsController extends BaseController implements ISO3166AwareInterface {

	use ShippingRateSchemaTrait;

	/**
	 * The base for routes in this controller.
	 *
	 * @var string
	 */
	protected $route_base = 'mc/shipping/rates/suggestions';

	/**
	 * @var ShippingSuggestionService
	 */
	protected $shipping_suggestion;

	/**
	 * ShippingRateSuggestionsController constructor.
	 *
	 * @param RESTServer                $server
	 * @param ShippingSuggestionService $shipping_suggestion
	 */
	public function __construct( RESTServer $server, ShippingSuggestionService $shipping_suggestion ) {
		parent::__construct( $server );
		$this->shipping_suggestion = $shipping_suggestion;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			"{$this->route_base}",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_suggestions_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'country_codes' => [
							'type'              => 'array',
							'description'       => __( 'Array of country codes in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
							'context'           => [ 'edit' ],
							'sanitize_callback' => $this->get_country_code_sanitize_callback(),
							'validate_callback' => $this->get_country_code_validate_callback(),
							'minItems'          => 1,
							'required'          => true,
							'uniqueItems'       => true,
							'items'             => [
								'type' => 'string',
							],
						],
					],
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for returning the endpoint results.
	 *
	 * @return callable
	 */
	protected function get_suggestions_callback(): callable {
		return function ( Request $request ) {
			$country_codes = $request->get_param( 'country_codes' );
			$rates_output  = [];
			foreach ( $country_codes as $country_code ) {
				$suggestions = $this->shipping_suggestion->get_suggestions( $country_code );

				// Prepare the output.
				$suggestions = array_map(
					function ( $suggestion ) use ( $request ) {
						$response = $this->prepare_item_for_response( $suggestion, $request );

						return $this->prepare_response_for_collection( $response );
					},
					$suggestions
				);

				// Merge the suggestions for all countries into one array.
				$rates_output = array_merge( $rates_output, $suggestions );
			}

			return $rates_output;
		};
	}

	/**
	 * @return array
	 */
	protected function get_schema_properties(): array {
		$schema = $this->get_shipping_rate_schema();

		// Suggested shipping rates don't have an id.
		unset( $schema['id'] );

		// All properties are read-only.
		return array_map(
			function ( $property ) {
				$property['readonly'] = true;
				$property['context']  = [ 'view' ];

				return $property;
			},
			$schema
		);
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'shipping_rates_suggestions';
	}
}
