<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PhoneNumber;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneVerificationController
 *
 * @since 1.5.0
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
	 * PhoneVerificationController constructor.
	 *
	 * @param RESTServer        $server
	 * @param PhoneVerification $phone_verification Phone verification service.
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
			'enum'              => [
				PhoneVerification::VERIFICATION_METHOD_SMS,
				PhoneVerification::VERIFICATION_METHOD_PHONE_CALL,
			],
			'required'          => true,
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		];

		$this->register_route(
			'/mc/phone-verification/request',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_request_phone_verification_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'phone_region_code'   => [
							'description'       => __( 'Two-letter country code (ISO 3166-1 alpha-2) for the phone number.', 'google-listings-and-ads' ),
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						],
						'phone_number'        => [
							'description'       => __( 'The phone number to verify.', 'google-listings-and-ads' ),
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						],
						'verification_method' => $verification_method,
					],
				],
			]
		);

		$this->register_route(
			'/mc/phone-verification/verify',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_verify_phone_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'verification_id'     => [
							'description'       => __( 'The verification ID returned by the /request call.', 'google-listings-and-ads' ),
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						],
						'verification_code'   => [
							'description'       => __( 'The verification code that was sent to the phone number for validation.', 'google-listings-and-ads' ),
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						],
						'verification_method' => $verification_method,
					],
				],
			]
		);
	}

	/**
	 * Get callback for requesting phone verification endpoint.
	 *
	 * @return callable
	 */
	protected function get_request_phone_verification_callback(): callable {
		return function( Request $request ) {
			try {
				$verification_id = $this->phone_verification->request_phone_verification(
					$request->get_param( 'phone_region_code' ),
					new PhoneNumber( $request->get_param( 'phone_number' ) ),
					$request->get_param( 'verification_method' ),
				);
				return [
					'verification_id' => $verification_id,
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get callback for verifying a phone number.
	 *
	 * @return callable
	 */
	protected function get_verify_phone_callback(): callable {
		return function( Request $request ) {
			try {
				$this->phone_verification->verify_phone_number(
					$request->get_param( 'verification_id' ),
					$request->get_param( 'verification_code' ),
					$request->get_param( 'verification_method' ),
				);
				return new Response( null, 204 );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
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
