<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopProductPagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopProductPagesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole
 */
class TopProductPagesController extends BaseController {

	/** @var TopProductPagesService */
	protected $service;

	/** @var ServiceBasedMerchantState */
	protected $service_based_merchant_state;

	/**
	 * TopProductPagesController constructor.
	 *
	 * @param RESTServer                $server
	 * @param TopProductPagesService    $service
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 */
	public function __construct( RESTServer $server, TopProductPagesService $service, ServiceBasedMerchantState $service_based_merchant_state ) {
		parent::__construct( $server );

		$this->service                      = $service;
		$this->service_based_merchant_state = $service_based_merchant_state;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'search-console/top-product-pages',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_top_product_pages_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'start_date' => $this->get_date_arg_schema( __( 'First day of the reporting range, as YYYY-MM-DD.', 'google-listings-and-ads' ) ),
						'end_date'   => $this->get_date_arg_schema( __( 'Last day of the reporting range, as YYYY-MM-DD.', 'google-listings-and-ads' ) ),
					],
				],
			]
		);
	}

	/**
	 * Get the callback function for the top product pages request.
	 *
	 * Service-based merchants have no products, so for them the data isn't available at all.
	 *
	 * @return callable
	 */
	protected function get_top_product_pages_callback(): callable {
		return function ( Request $request ) {
			if ( $this->service_based_merchant_state->is_service_based_merchant() ) {
				return new Response(
					[ 'message' => __( 'Top product pages are not available for service-based merchants.', 'google-listings-and-ads' ) ],
					404
				);
			}

			try {
				return $this->service->get_top_product_pages(
					$request->get_param( 'start_date' ),
					$request->get_param( 'end_date' )
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the schema of a required `YYYY-MM-DD` request argument.
	 *
	 * @param string $description
	 *
	 * @return array
	 */
	protected function get_date_arg_schema( string $description ): array {
		return [
			'description'       => $description,
			'type'              => 'string',
			'required'          => true,
			'validate_callback' => [ $this, 'validate_date' ],
			'sanitize_callback' => 'sanitize_text_field',
		];
	}

	/**
	 * Whether a value is a real calendar date written as YYYY-MM-DD.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function validate_date( $value ): bool {
		if ( ! is_string( $value ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) ) {
			return false;
		}

		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'search_console_top_product_pages';
	}
}
