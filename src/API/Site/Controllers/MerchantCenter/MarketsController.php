<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class MarketsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class MarketsController extends BaseController {

	/**
	 * @var MarketService
	 */
	protected MarketService $market_service;

	/**
	 * MarketsController constructor.
	 *
	 * @param RESTServer    $server
	 * @param MarketService $market_service
	 */
	public function __construct( RESTServer $server, MarketService $market_service ) {
		parent::__construct( $server );
		$this->market_service = $market_service;
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
				'schema' => $this->get_languages_currencies_schema_callback(),
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
			$markets = [];
			foreach ( $this->market_service->get_markets() as $id => $market ) {
				$market['id'] = $id;
				$response     = $this->prepare_item_for_response( $market, $request );
				$markets[]    = $this->prepare_response_for_collection( $response );
			}
			return $markets;
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
					'languages'  => $this->market_service->get_languages(),
					'currencies' => $this->market_service->get_currencies(),
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
		return function ( Request $request ) {
			$config = [
				'country'    => $request->get_param( 'country' ),
				'feed_label' => $request->get_param( 'feed_label' ) ?? $request->get_param( 'country' ),
			];

			if ( null !== $request->get_param( 'language' ) ) {
				$config['language'] = $request->get_param( 'language' );
			}

			if ( null !== $request->get_param( 'currency' ) ) {
				$config['currency'] = $request->get_param( 'currency' );
			}

			if ( null !== $request->get_param( 'exchange_rate' ) ) {
				$config['exchange_rate'] = $request->get_param( 'exchange_rate' );
			}

			try {
				$id = $this->market_service->generate_market_id( $config['feed_label'] );
			} catch ( InvalidValue $e ) {
				return new Response(
					[ 'message' => __( 'Cannot create a market with a reserved ID.', 'google-listings-and-ads' ) ],
					400
				);
			}

			if ( null !== $this->market_service->get_market( $id ) ) {
				return new Response(
					[
						'message' => __( 'A market with this ID already exists.', 'google-listings-and-ads' ),
						'id'      => $id,
					],
					409
				);
			}

			try {
				$this->market_service->add_market( $id, $config );
			} catch ( InvalidValue $e ) {
				return new Response( [ 'message' => $e->getMessage() ], 400 );
			}

			$created       = $this->market_service->get_market( $id );
			$created['id'] = $id;

			return new Response( $created, 201 );
		};
	}

	/**
	 * Get the callback for updating a market.
	 *
	 * Dispatches to MarketService::update_market() for all markets including
	 * primary. The AC mentions "POST .../primary" in the cross-cutting section,
	 * but this is treated as a typo for "PUT .../primary" — the preceding AC
	 * section documents PUT /mc/markets/primary returning 200.
	 *
	 * @return callable
	 */
	protected function get_update_market_callback(): callable {
		return function ( Request $request ) {
			$id     = $request->get_param( 'id' );
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

			try {
				$updated = $this->market_service->update_market( $id, $this->extract_writable_params( $request ) );
			} catch ( InvalidValue $e ) {
				return new Response( [ 'message' => $e->getMessage() ], 400 );
			}

			return new Response( $updated );
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

			try {
				$this->market_service->delete_market( $id );
			} catch ( InvalidValue $e ) {
				return new Response( [ 'message' => $e->getMessage() ], 400 );
			}

			return new Response(
				[
					'deleted' => true,
					'id'      => $id,
				]
			);
		};
	}

	/**
	 * Extracts only the writable (edit-context) params that were actually
	 * supplied on the request, so omitted fields are not modified (partial update).
	 *
	 * @param Request $request
	 *
	 * @return array
	 */
	private function extract_writable_params( Request $request ): array {
		$schema = $this->get_schema_properties();
		$params = [];

		foreach ( $schema as $key => $definition ) {
			if ( ! empty( $definition['readonly'] ) ) {
				continue;
			}

			if ( ! in_array( 'edit', $definition['context'] ?? [], true ) ) {
				continue;
			}

			$value = $request->get_param( $key );
			if ( null !== $value ) {
				$params[ $key ] = $value;
			}
		}

		return $params;
	}

	/**
	 * Get the required args schema for the POST create endpoint.
	 *
	 * @return array
	 */
	protected function get_create_market_args(): array {
		$schema = $this->get_schema_properties();

		return [
			'country' => array_merge( $schema['country'], [ 'required' => true ] ),
		];
	}

	/**
	 * Get the schema callback for the languages-currencies endpoint.
	 *
	 * @return callable
	 */
	protected function get_languages_currencies_schema_callback(): callable {
		return function () {
			return [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'languages-currencies',
				'type'       => 'object',
				'properties' => [
					'languages'  => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'code'  => [ 'type' => 'string' ],
								'label' => [ 'type' => 'string' ],
							],
						],
					],
					'currencies' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'code'      => [ 'type' => 'string' ],
								'symbol'    => [ 'type' => 'string' ],
								'languages' => [
									'description' => __( 'Active language codes the currency is enabled for.', 'google-listings-and-ads' ),
									'type'        => 'array',
									'items'       => [ 'type' => 'string' ],
								],
							],
						],
					],
				],
			];
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * The AC uses "country" (singular) for the primary target country field.
	 * The PR test plan and FE expect "countries" (plural) for the array of
	 * country codes — this is the semantically correct name for an array.
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
				'description'       => __( 'Primary country code in ISO 3166-1 alpha-2 format. Null for the primary market.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'language'      => [
				'type'              => 'array',
				'description'       => __( 'Language codes in ISO 639-1 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'items'             => [ 'type' => 'string' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'currency'      => [
				'type'              => 'array',
				'description'       => __( 'Currency codes in ISO 4217 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'items'             => [ 'type' => 'string' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'exchange_rate' => [
				'type'              => 'number',
				'description'       => __( 'Fixed exchange rate applied to store prices for this market: units of market currency per unit of store currency. Lets a secondary market use a currency the site cannot otherwise produce. Ignored for the primary market.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'feed_label'    => [
				'type'              => 'string',
				'description'       => __( 'Google feed label. Null for the primary market.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
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
