<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class SupportedCountriesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class SupportedCountriesController extends BaseController {

	use CountryCodeTrait;
	use EmptySchemaPropertiesTrait;
	use GoogleHelper;

	/**
	 * The WC proxy object.
	 *
	 * @var WC
	 */
	protected $wc;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param WC         $wc
	 */
	public function __construct( RESTServer $server, WC $wc ) {
		parent::__construct( $server );
		$this->wc = $wc;
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
		return function() {
			return $this->get_supported_countries();
		};
	}

	/**
	 * Get the array of supported countries.
	 *
	 * @return array
	 */
	protected function get_supported_countries(): array {
		$all_countries = $this->wc->get_countries();
		$mc_countries  = $this->get_mc_supported_countries_currencies();

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
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'supported_countries';
	}
}
