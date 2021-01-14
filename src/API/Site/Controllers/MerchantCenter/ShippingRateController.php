<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ControllerTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Psr\Container\ContainerInterface;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingRateController extends BaseOptionsController {

	use ControllerTrait;
	use CountryCodeTrait;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * The base for routes in this controller.
	 *
	 * @var string
	 */
	protected $route_base = 'mc/shipping/rates';

	/**
	 * ShippingRateController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ), $container->get( OptionsInterface::class ) );
		$this->iso = $container->get( ISO3166DataProvider::class );
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			$this->route_base,
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_rates_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_create_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_schema(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			"{$this->route_base}/(?P<country_code>\\w{2})",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_schema(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
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
	protected function get_read_rates_callback(): callable {
		return function() {
			$rates = $this->get_shipping_rates_option();
			$items = [];
			foreach ( $rates as $country_code => $details ) {
				$items[ $country_code ] = $this->prepare_item_for_response( $details );
			}

			return $items;
		};
	}

	/**
	 * @return callable
	 */
	protected function get_read_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$country = $request->get_param( 'country_code' );
			$rates   = $this->get_shipping_rates_option();
			if ( ! array_key_exists( $country, $rates ) ) {
				return new WP_REST_Response(
					[
						'message' => __( 'No rate available.', 'google-listings-and-ads' ),
						'country' => $country,
					],
					404
				);
			}

			return $this->prepare_item_for_response( $rates[ $country ] );
		};
	}

	/**
	 * Get the callback function for creating a new shipping rate.
	 *
	 * @return callable
	 */
	protected function get_create_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$iso = $request->get_param( 'country_code' );
			$this->update_shipping_rates_option(
				$this->process_new_rate(
					$this->get_shipping_rates_option(),
					$iso,
					$request->get_params()
				)
			);

			return new WP_REST_Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully added rate for country.', 'google-listings-and-ads' ),
				],
				201
			);
		};
	}

	/**
	 * @return callable
	 */
	protected function get_delete_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$iso   = $request->get_param( 'country_code' );
			$rates = $this->get_shipping_rates_option();
			unset( $rates[ $iso ] );
			$this->update_shipping_rates_option( $rates );

			return [
				'status'  => 'success',
				'message' => sprintf(
					/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
					__( 'Successfully deleted the rate for country "%s".', 'google-listings-and-ads' ),
					$iso
				),
			];
		};
	}

	/**
	 * @return array
	 */
	protected function get_item_schema(): array {
		return [
			'country'      => [
				'type'        => 'string',
				'description' => __( 'Country in which the shipping rate applies.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'country_code' => [
				'type'              => 'string',
				'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'required'          => true,
			],
			'currency'     => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => 'USD', // todo: default to store currency.
			],
			'rate'         => [
				'type'              => 'integer',
				'description'       => __( 'The shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
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
	protected function get_item_schema_name(): string {
		return 'shipping_rates';
	}

	/**
	 * Get the array of shipping rates from the option store.
	 *
	 * @return array
	 */
	protected function get_shipping_rates_option(): array {
		return $this->options->get( OptionsInterface::SHIPPING_RATES, [] );
	}

	/**
	 * Update the array of shipping rates in the options store.
	 *
	 * @param array $rates
	 *
	 * @return bool
	 */
	protected function update_shipping_rates_option( array $rates ): bool {
		return $this->options->update( OptionsInterface::SHIPPING_RATES, $rates );
	}

	/**
	 * Process a new rate and add it to the other rates.
	 *
	 * @param array  $all_rates Array of all rates.
	 * @param string $rate_key  The rate key.
	 * @param array  $raw_data  Raw data to process.
	 *
	 * @return array
	 */
	protected function process_new_rate( array $all_rates, string $rate_key, array $raw_data ): array {
		// Specifically call the schema method from this class.
		$schema = self::get_item_schema();

		$rate = $all_rates[ $rate_key ] ?? [];
		foreach ( $schema as $key => $property ) {
			$rate[ $key ] = $raw_data[ $key ] ?? $rate[ $key ] ?? $property['default'] ?? null;
		}

		// todo: translate the country using WC_Countries class
		$rate['country']        = $this->iso->alpha2( $rate_key )['name'];
		$all_rates[ $rate_key ] = $rate;

		return $all_rates;
	}
}
