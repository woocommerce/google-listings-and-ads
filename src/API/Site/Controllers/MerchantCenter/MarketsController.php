<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class MarketsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class MarketsController extends BaseOptionsController {

	/**
	 * @var MarketService
	 */
	protected MarketService $market_service;

	/**
	 * @var TargetAudience
	 */
	protected TargetAudience $target_audience;

	/**
	 * MarketsController constructor.
	 *
	 * @param RESTServer     $server
	 * @param MarketService  $market_service
	 * @param TargetAudience $target_audience
	 */
	public function __construct( RESTServer $server, MarketService $market_service, TargetAudience $target_audience ) {
		parent::__construct( $server );
		$this->market_service  = $market_service;
		$this->target_audience = $target_audience;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/markets',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_markets_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_create_market_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_create_market_args(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			'mc/markets/languages-currencies',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_languages_currencies_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'mc/markets/(?P<id>[a-z0-9-]+)',
			[
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_update_market_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_market_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback for reading all markets.
	 *
	 * @return callable
	 */
	protected function get_read_markets_callback(): callable {
		return function ( Request $request ) {
			$primary = $this->build_primary_market();

			$markets = [ $primary ];

			$stored = $this->market_service->get_markets();
			foreach ( $stored as $key => $market ) {
				if ( is_string( $key ) ) {
					$market['id'] = $key;
					$markets[]    = $market;
				}
			}

			return array_map(
				function ( $market ) use ( $request ) {
					$response = $this->prepare_item_for_response( $market, $request );
					return $this->prepare_response_for_collection( $response );
				},
				$markets
			);
		};
	}

	/**
	 * Get the callback for the languages-currencies endpoint.
	 *
	 * @return callable
	 */
	protected function get_languages_currencies_callback(): callable {
		return function () {
			return new Response(
				[
					'languages'  => [],
					'currencies' => [],
				]
			);
		};
	}

	/**
	 * Get the callback for creating a market.
	 *
	 * @return callable
	 */
	protected function get_create_market_callback(): callable {
		return function () {
			// TODO: Implement market creation in GOOWOO-559.
			return new Response(
				[
					'message' => __( 'Not yet implemented.', 'google-listings-and-ads' ),
				],
				501
			);
		};
	}

	/**
	 * Get the callback for updating a market.
	 *
	 * @return callable
	 */
	protected function get_update_market_callback(): callable {
		return function ( Request $request ) {
			$id = $request->get_param( 'id' );

			if ( 'primary' === $id ) {
				return $this->update_primary_market( $request );
			}

			$market = $this->market_service->get_market( $id );
			if ( null === $market ) {
				return new Response(
					[
						'message' => __( 'Market not found.', 'google-listings-and-ads' ),
						'id'      => $id,
					],
					404
				);
			}

			// TODO: Implement secondary market updates in GOOWOO-559.
			return new Response(
				[
					'message' => __( 'Not yet implemented.', 'google-listings-and-ads' ),
				],
				501
			);
		};
	}

	/**
	 * Get the callback for deleting a market.
	 *
	 * @return callable
	 */
	protected function get_delete_market_callback(): callable {
		return function ( Request $request ) {
			$id = $request->get_param( 'id' );

			if ( 'primary' === $id ) {
				return new Response(
					[
						'message' => __( 'The primary market cannot be deleted.', 'google-listings-and-ads' ),
					],
					400
				);
			}

			$market = $this->market_service->get_market( $id );
			if ( null === $market ) {
				return new Response(
					[
						'message' => __( 'Market not found.', 'google-listings-and-ads' ),
						'id'      => $id,
					],
					404
				);
			}

			// TODO: Implement market deletion in GOOWOO-559.
			return new Response(
				[
					'message' => __( 'Not yet implemented.', 'google-listings-and-ads' ),
				],
				501
			);
		};
	}

	/**
	 * Build the primary market object from existing settings.
	 *
	 * @return array
	 */
	protected function build_primary_market(): array {
		$primary  = $this->market_service->get_primary_market();
		$settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		return [
			'id'            => 'primary',
			'label'         => __( 'Primary Market', 'google-listings-and-ads' ),
			'countries'     => $this->target_audience->get_target_countries(),
			'country'       => $primary['country'] ?? null,
			'language'      => $primary['language'] ?? null,
			'currency'      => $primary['currency'] ?? null,
			'feedLabel'     => $primary['feedLabel'] ?? null,
			'shipping_rate' => $settings['shipping_rate'] ?? null,
			'shipping_time' => $settings['shipping_time'] ?? null,
			'free_shipping' => null,
		];
	}

	/**
	 * Handle updating the primary market.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 */
	protected function update_primary_market( Request $request ): Response {
		$settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		if ( null !== $request->get_param( 'shipping_rate' ) ) {
			$settings['shipping_rate'] = $request->get_param( 'shipping_rate' );
		}

		if ( null !== $request->get_param( 'shipping_time' ) ) {
			$settings['shipping_time'] = $request->get_param( 'shipping_time' );
		}

		$this->options->update( OptionsInterface::MERCHANT_CENTER, $settings );

		$countries = $request->get_param( 'countries' );
		if ( null !== $countries ) {
			$audience              = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );
			$audience['countries'] = $countries;
			$this->options->update( OptionsInterface::TARGET_AUDIENCE, $audience );
		}

		$primary = $this->market_service->get_primary_market();

		return new Response(
			[
				'id'            => 'primary',
				'label'         => __( 'Primary Market', 'google-listings-and-ads' ),
				'countries'     => $countries ?? $this->target_audience->get_target_countries(),
				'country'       => $primary['country'] ?? null,
				'language'      => $primary['language'] ?? null,
				'currency'      => $primary['currency'] ?? null,
				'feedLabel'     => $primary['feedLabel'] ?? null,
				'shipping_rate' => $settings['shipping_rate'] ?? null,
				'shipping_time' => $settings['shipping_time'] ?? null,
				'free_shipping' => null,
			]
		);
	}

	/**
	 * Get the required args schema for the POST create endpoint.
	 *
	 * @return array
	 */
	protected function get_create_market_args(): array {
		$schema = $this->get_schema_properties();

		return [
			'country'       => array_merge( $schema['country'], [ 'required' => true ] ),
			'language'      => array_merge( $schema['language'], [ 'required' => true ] ),
			'currency'      => array_merge( $schema['currency'], [ 'required' => true ] ),
			'shipping_rate' => array_merge( $schema['shipping_rate'], [ 'required' => true ] ),
			'shipping_time' => array_merge( $schema['shipping_time'], [ 'required' => true ] ),
			'free_shipping' => $schema['free_shipping'],
		];
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'            => [
				'type'              => 'string',
				'description'       => __( 'Unique market identifier.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			],
			'label'         => [
				'type'              => 'string',
				'description'       => __( 'Human-readable market label.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			],
			'countries'     => [
				'type'              => 'array',
				'description'       => __( 'Array of country codes in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'items'             => [ 'type' => 'string' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'country'       => [
				'type'              => 'string',
				'description'       => __( 'Primary country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'language'      => [
				'type'              => 'string',
				'description'       => __( 'Language code in ISO 639-1 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'currency'      => [
				'type'              => 'string',
				'description'       => __( 'Currency code in ISO 4217 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'feedLabel'     => [
				'type'              => 'string',
				'description'       => __( 'Google feed label.', 'google-listings-and-ads' ),
				'context'           => [ 'view' ],
				'readonly'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			],
			'shipping_rate' => [
				'type'              => 'string',
				'description'       => __( 'Shipping rate configuration type.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'enum'              => [ 'automatic', 'flat', 'manual' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'shipping_time' => [
				'type'              => 'string',
				'description'       => __( 'Shipping time configuration type.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'enum'              => [ 'flat', 'manual' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'free_shipping' => [
				'type'              => [ 'number', 'null' ],
				'description'       => __( 'Free shipping threshold amount, or null when unset.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'market';
	}
}
