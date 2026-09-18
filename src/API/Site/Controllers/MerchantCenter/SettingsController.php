<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class SettingsController extends BaseOptionsController {

	/** @var string[] Schema keys stored in the dedicated GOOGLE_CUSTOMER_REVIEWS option, not MERCHANT_CENTER. */
	protected const GCR_SCHEMA_KEYS = [
		'gcr_collect_reviews_after_purchase',
		'gcr_badge_widget_enabled',
		'gcr_badge_widget_position',
	];

	/**
	 * @var ShippingZone
	 */
	protected $shipping_zone;

	/**
	 * @var MarketService
	 */
	protected $market_service;

	/**
	 * SettingsController constructor.
	 *
	 * @param RESTServer    $server
	 * @param ShippingZone  $shipping_zone
	 * @param MarketService $market_service
	 */
	public function __construct( RESTServer $server, ShippingZone $shipping_zone, MarketService $market_service ) {
		parent::__construct( $server );
		$this->shipping_zone  = $shipping_zone;
		$this->market_service = $market_service;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/settings',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_settings_endpoint_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_settings_endpoint_edit_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get a callback for the settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_read_callback(): callable {
		return function () {
			$mc_options  = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
			$gcr_options = $this->options->get( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS, [] );

			$data                         = array_merge(
				is_array( $mc_options ) ? $mc_options : [],
				is_array( $gcr_options ) ? $gcr_options : []
			);
			$data['shipping_rates_count'] = $this->shipping_zone->get_shipping_rates_count();
			$schema                       = $this->get_schema_properties();
			$items                        = [];
			foreach ( $schema as $key => $property ) {
				$items[ $key ] = $data[ $key ] ?? $property['default'] ?? null;
			}

			return $items;
		};
	}

	/**
	 * Get a callback for editing the settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_edit_callback(): callable {
		return function ( Request $request ) {
			$schema      = $this->get_schema_properties();
			$mc_options  = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
			$gcr_options = $this->options->get( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS, [] );
			if ( ! is_array( $mc_options ) ) {
				$mc_options = [];
			}
			if ( ! is_array( $gcr_options ) ) {
				$gcr_options = [];
			}

			$previous_shipping = [
				'shipping_rate' => $mc_options['shipping_rate'] ?? null,
				'shipping_time' => $mc_options['shipping_time'] ?? null,
			];

			$current = array_merge( $mc_options, $gcr_options );

			foreach ( $schema as $key => $property ) {
				if ( ! in_array( 'edit', $property['context'] ?? [], true ) ) {
					continue;
				}

				$value = $request->get_param( $key ) ?? $current[ $key ] ?? $property['default'] ?? null;

				if ( in_array( $key, self::GCR_SCHEMA_KEYS, true ) ) {
					$gcr_options[ $key ] = $value;
				} else {
					$mc_options[ $key ] = $value;
				}
			}

			$this->options->update( OptionsInterface::MERCHANT_CENTER, $mc_options );

			// Only persist GOOGLE_CUSTOMER_REVIEWS when the request actually touched one of its
			// keys — otherwise a merchant editing an unrelated Merchant Center setting (e.g.
			// shipping_rate) would create the option with default values and fire
			// woocommerce_gla_options_updated_google_customer_reviews for a save that never
			// touched GCR at all.
			if ( array_intersect( array_keys( $request->get_params() ), self::GCR_SCHEMA_KEYS ) ) {
				$this->options->update( OptionsInterface::GOOGLE_CUSTOMER_REVIEWS, $gcr_options );
			}

			// The global shipping method is what every market's Merchant Center shipping service is
			// generated from, but this save path never told MarketService, so a mode switch here
			// used not to reach Google at all. Only trigger the resync when the method actually
			// changed, to avoid scheduling a job on unrelated settings saves.
			$shipping_method_changed = $previous_shipping['shipping_rate'] !== ( $mc_options['shipping_rate'] ?? null )
				|| $previous_shipping['shipping_time'] !== ( $mc_options['shipping_time'] ?? null );

			if ( $shipping_method_changed ) {
				$this->market_service->handle_global_shipping_method_change();
			}

			return [
				'status'  => 'success',
				'message' => __( 'Merchant Center Settings successfully updated.', 'google-listings-and-ads' ),
				'data'    => array_merge( $mc_options, $gcr_options ),
			];
		};
	}

	/**
	 * Get the schema for settings endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'shipping_rate'                      => [
				'type'              => 'string',
				'description'       => __(
					'Whether shipping rate is a simple flat rate or needs to be configured manually in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'automatic',
					'flat',
					'manual',
				],
			],
			'shipping_time'                      => [
				'type'              => 'string',
				'description'       => __(
					'Whether shipping time is a simple flat time or needs to be configured manually in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'flat',
					'manual',
				],
			],
			'tax_rate'                           => [
				'type'              => 'string',
				'description'       => __(
					'Whether tax rate is destination based or need to be configured manually in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'destination',
					'manual',
				],
				'default'           => 'destination',
			],
			'shipping_rates_count'               => [
				'type'              => 'number',
				'description'       => __(
					'The number of shipping rates in WC ready to be used in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => 0,
			],
			'gcr_collect_reviews_after_purchase' => [
				'type'              => 'boolean',
				'description'       => __(
					'Whether to inject the Google Customer Reviews opt-in prompt on the order-confirmation page.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'gcr_badge_widget_enabled'           => [
				'type'              => 'boolean',
				'description'       => __(
					'Whether the Google-verified ratings and reviews badge widget is enabled on the store.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'gcr_badge_widget_position'          => [
				'type'              => 'string',
				'description'       => __(
					'The corner of the storefront in which to display the ratings and reviews badge widget.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'bottom-left',
					'bottom-right',
				],
				'default'           => 'bottom-right',
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
		return 'merchant_center_settings';
	}
}
