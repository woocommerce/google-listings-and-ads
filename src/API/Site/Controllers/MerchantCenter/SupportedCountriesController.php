<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class SupportedCountriesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class SupportedCountriesController extends BaseController {

	use CountryCodeTrait;
	use EmptySchemaPropertiesTrait;

	/**
	 * The WC proxy object.
	 *
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var GoogleHelper
	 */
	protected $google_helper;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer   $server
	 * @param WC           $wc
	 * @param GoogleHelper $google_helper
	 */
	public function __construct( RESTServer $server, WC $wc, GoogleHelper $google_helper ) {
		parent::__construct( $server );
		$this->wc            = $wc;
		$this->google_helper = $google_helper;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/countries',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_countries_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_query_args(),
				],
			]
		);
	}

	/**
	 * Get the callback function for returning supported countries.
	 *
	 * @return callable
	 */
	protected function get_countries_callback(): callable {
		return function( Request $request ) {
			$return = [
				'countries' => $this->get_supported_countries( $request ),
			];

			if ( $request->get_param( 'continents' ) ) {
				$return['continents'] = $this->get_supported_continents();
			}

			return $return;
		};
	}

	/**
	 * Get the array of supported countries.
	 *
	 * @return array
	 */
	protected function get_supported_countries(): array {
		$all_countries = $this->wc->get_countries();
		$mc_countries  = $this->google_helper->get_mc_supported_countries_currencies();

		$supported = [];
		foreach ( $mc_countries as $country => $currency ) {
			if ( ! array_key_exists( $country, $all_countries ) ) {
				continue;
			}

			$supported[ $country ] = [
				'name'     => $all_countries[ $country ],
				'currency' => $currency,
			];
		}

		uasort(
			$supported,
			function( $a, $b ) {
				return $a['name'] <=> $b['name'];
			}
		);

		return $supported;
	}

	/**
	 * Get the array of supported continents.
	 *
	 * @return array
	 */
	protected function get_supported_continents(): array {
		$all_continents = $this->wc->get_continents();

		foreach ( $all_continents as $continent_code => $continent ) {
			$supported_countries_of_continent = $this->google_helper->get_supported_countries_from_continent( $continent_code );

			if ( empty( $supported_countries_of_continent ) ) {
				unset( $all_continents[ $continent_code ] );
			} else {
				$all_continents[ $continent_code ]['countries'] = array_values( $supported_countries_of_continent );
			}
		}

		return $all_continents;
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'supported_countries';
	}

	/**
	 * Get the arguments for the query endpoint.
	 *
	 * @return array
	 */
	protected function get_query_args(): array {
		return [
			'continents' => [
				'description'       => __( 'Include continents data if set to true.', 'google-listings-and-ads' ),
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
