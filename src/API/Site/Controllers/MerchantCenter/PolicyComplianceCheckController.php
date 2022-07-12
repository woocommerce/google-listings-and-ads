<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PolicyComplianceCheck;
use Exception;
use WP_REST_Request as Request;


defined( 'ABSPATH' ) || exit;

/**
 * Class PolicyComplianceCheckController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class PolicyComplianceCheckController extends BaseController {

	use CountryCodeTrait;

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
			'mc/policy_check',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_policy_check_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the allowed countries, payment gateways info, store ssl and refund return policy page for the controller.
	 *
	 * @return callable
	 */
	protected function get_policy_check_callback(): callable {
		return function () {
			try {
				return [
					'policy_check' => [
						'allowed_countries' => $this->policy_compliance_check->get_allowed_countries(),
						'payment_gateways'  => $this->policy_compliance_check->has_payment_gateways(),
						'store_ssl'         => $this->policy_compliance_check->get_is_store_ssl(),
						'refund_returns'    => $this->policy_compliance_check->has_refund_return_policy_page(),
					],
				];

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
			'policy_check' => [
				'allowed_countries' => [
					'type'        => 'array',
					'description' => __( 'The allowed countries where the store could be accessed.', 'google-listings-and-ads' ),
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
