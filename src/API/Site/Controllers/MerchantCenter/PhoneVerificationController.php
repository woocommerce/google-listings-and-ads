<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneVerificationController
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class PhoneVerificationController extends BaseOptionsController {

	use EmptySchemaPropertiesTrait;

	/**
	 * @var PhoneVerification
	 */
	protected $phone_verification;

	/**
	 * ContactInformationController constructor.
	 *
	 * @param RESTServer        $server
	 * @param PhoneVerification $phone_verification
	 */
	public function __construct( RESTServer $server, PhoneVerification $phone_verification ) {
		parent::__construct( $server );
		$this->phone_verification = $phone_verification;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$verification_method = [
			'description'       => __( 'Method used to verify the phone number.', 'google-listings-and-ads' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
			'required'          => true,
		];

		$this->register_route(
			'/mc/phone-verification/request',
			[
				[
					'method'              => TransportMethods::CREATABLE,
					'callback'            => $this->get_request_phone_verification_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'context'             => $this->get_context_param( [ 'default' => 'view' ] ),
						'phone_number'        => [
							'description'       => __( 'The phone number to verify.', 'google-listings-and-ads' ),
							'type'              => [ 'integer', 'string' ],
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						],
						'verification_method' => $verification_method,
					],
				]
			]
		);

		$this->register_route(
			'/mc/phone-verification/verify',
			[
				[
					'method'              => TransportMethods::CREATABLE,
					'callback'            => $this->get_verify_phone_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'context'                   => $this->get_context_param( [ 'default' => 'view' ] ),
						'verification_id'           => [
							'description'       => __( 'The verification ID returned by the /request call.', 'google-listings-and-ads' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						],
						'verification_code' => [
							'description'       => __( 'The verification code that was sent to the phone number for validation.', 'google-listings-and-ads' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						],
						'verification_method' => $verification_method,
					],
				]
			]
		);
	}

	/**
	 * Get callback for requesting phone verification endpoint.
	 *
	 * @return callable
	 */
	protected function get_request_phone_verification_callback() {

	}

	/**
	 * Get callback for verifying a phone number.
	 *
	 * @return callable
	 */
	protected function get_verify_phone_callback() {

	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'phone_verification';
	}
}
