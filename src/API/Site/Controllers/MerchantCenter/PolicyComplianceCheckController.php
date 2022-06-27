<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PolicyComplianceCheck;

defined( 'ABSPATH' ) || exit;

/**
 * Class PolicyComplianceCheckController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class PolicyComplianceCheckController extends BaseController {

	use CountryCodeTrait;
	use EmptySchemaPropertiesTrait;

	/**
	 * The PolicyComplianceCheck object.
	 *
	 * @var PolicyComplianceCheck
	 */
	protected $policy_compliance_check;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer            $server
	 * @param PolicyComplianceCheck $policy_compliance_check
	 */
	public function __construct( RESTServer $server, PolicyComplianceCheck $policy_compliance_check ) {
		parent::__construct( $server );
		$this->policy_compliance_check = $policy_compliance_check;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/policy_check/allowed_countries',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_allowed_countries_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'mc/policy_check/payment_gateways',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->has_payment_gateways_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'mc/policy_check/store_ssl',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_is_store_ssl_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'mc/policy_check/refund_return_policy',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->has_refund_return_policy_page_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the allowed countries for the controller.
	 *
	 * @return callable
	 */
	protected function get_allowed_countries_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->policy_compliance_check->get_allowed_countries();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Check if the payment gateways is empty or not for the controller.
	 *
	 * @return callable
	 */
	protected function has_payment_gateways_callback(): callable {
		return function ( Request $request ) {
			try {

				return $this->policy_compliance_check->has_payment_gateways();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Check if the store is using SSL for the controller.
	 *
	 * @return callable
	 */
	protected function get_is_store_ssl_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->policy_compliance_check->get_is_store_ssl();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};

	}

	/**
	 * Check if the store has refund return policy page for the controller.
	 *
	 * @return callable
	 */
	protected function has_refund_return_policy_page_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->policy_compliance_check->has_refund_return_policy_page();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the schema for policy compliance check endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'allowed_countries' => [
				'type'        => 'array',
				'description' => __( 'The allowed countries associated with onboarding policy checking.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'payment_gateways'  => [
				'type'        => 'boolean',
				'description' => __( 'The payment gateways associated with onboarding policy checking.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'store_ssl'         => [
				'type'        => 'boolean',
				'description' => __( 'The store ssl associated with onboarding policy checking.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'refund_returns'    => [
				'type'        => 'boolean',
				'description' => __( 'The refund returns policy associated with onboarding policy checking.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
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
		return 'policy_check';
	}
}
