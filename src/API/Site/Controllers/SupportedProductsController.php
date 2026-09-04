<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for recording a merchant's supported-products confirmation.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class SupportedProductsController extends BaseController {

	use EmptySchemaPropertiesTrait;

	/**
	 * Service-based merchant state.
	 *
	 * @var ServiceBasedMerchantState
	 */
	protected ServiceBasedMerchantState $service_based_merchant_state;

	/**
	 * SupportedProductsController constructor.
	 *
	 * @param RESTServer                $server REST server proxy.
	 * @param ServiceBasedMerchantState $service_based_merchant_state Service-based merchant state.
	 */
	public function __construct( RESTServer $server, ServiceBasedMerchantState $service_based_merchant_state ) {
		parent::__construct( $server );
		$this->service_based_merchant_state = $service_based_merchant_state;
	}

	/**
	 * Register REST routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'merchant/supported-products',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_confirm_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'confirmed' => [
							'type'              => 'boolean',
							'enum'              => [ true ],
							'required'          => true,
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'rest_sanitize_boolean',
						],
					],
				],
			]
		);
	}

	/**
	 * Get the callback for confirming supported products.
	 *
	 * @return callable
	 */
	protected function get_confirm_callback(): callable {
		return function ( Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			$this->service_based_merchant_state->confirm_supported_products();

			return new Response(
				[
					'confirmed'              => true,
					'service_based_merchant' => false,
				],
				200
			);
		};
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'supported_products';
	}
}
