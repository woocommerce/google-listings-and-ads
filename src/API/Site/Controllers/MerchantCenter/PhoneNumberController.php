<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Google\Service\ShoppingContent\Account;
use Exception;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneNumberController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class PhoneNumberController extends BaseController {

	/**
	 * @var Merchant $merchant
	 */
	protected $merchant;

	/**
	 * PhoneNumberController constructor.
	 *
	 * @param RESTServer $server
	 * @param Merchant   $merchant
	 */
	public function __construct( RESTServer $server, Merchant $merchant ) {
		parent::__construct( $server );
		$this->merchant = $merchant;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/phone-number',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_phone_number_endpoint_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_phone_number_endpoint_edit_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_update_args(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get a callback for the phone number endpoint.
	 *
	 * @return callable
	 */
	protected function get_phone_number_endpoint_read_callback(): callable {
		return function( $request ) {
			try {
				$account = $this->merchant->get_account();
				return $this->get_phone_number_response( $request, $account );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get a callback for the edit phone number endpoint.
	 *
	 * @return callable
	 */
	protected function get_phone_number_endpoint_edit_callback(): callable {
		return function( Request $request ) {
			try {
				$account              = $this->merchant->get_account();
				$business_information = $account->getBusinessInformation();
				$business_information->setPhoneNumber( $request['phone_number'] );
				$account->setBusinessInformation( $business_information );
				$this->merchant->update_account( $account );
				return $this->get_phone_number_response( $request, $account );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the schema for phone number endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'           => [
				'type'              => 'integer',
				'description'       => __( 'The Merchant Center account ID.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'phone_number' => [
				'type'              => 'string',
				'description'       => __( 'The phone number associated with the Merchant Center account.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the arguments for the update endpoint.
	 *
	 * @return array
	 */
	public function get_update_args(): array {
		return [
			'context'           => $this->get_context_param( [ 'default' => 'view' ] ),
			'phone_number'      => [
				'description'       => __( 'The new phone number to assign to the account.', 'google-listings-and-ads' ),
				'type'              => [ 'integer', 'string' ],
				'validate_callback' => $this->get_phone_number_validate_callback(),
				'sanitize_callback' => $this->get_phone_number_sanitize_callback(),
			],
			'verification_code' => [
				'description'       => __( 'The verification code for the phone number.', 'google-listings-and-ads' ),
				'type'              => [ 'integer', 'string' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the prepared REST response with Merchant Center account ID and phone number.
	 *
	 * @param Request $request
	 * @param Account $account
	 *
	 * @return Response
	 */
	protected function get_phone_number_response( Request $request, Account $account ): Response {
		return $this->prepare_item_for_response(
			[
				'id'           => $account->getId(),
				'phone_number' => $account->getBusinessInformation()->getPhoneNumber() ?: '',
			],
			$request
		);
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'merchant_center_phone_number';
	}

	/**
	 * Get the callback to sanitize the phone number, leaving only `+` (plus) and numbers.
	 *
	 * @return callable
	 */
	protected function get_phone_number_sanitize_callback(): callable {
		return function( $phone_number ) {
			return preg_replace( '/[^\+0-9]/', '', "$phone_number" );
		};
	}

	/**
	 * Validate that the phone number doesn't contain invalid characters.
	 * Allowed: ()-.0123456789 and space
	 *
	 * @return callable
	 */
	protected function get_phone_number_validate_callback() {
		return function( $value, $request, $param ) {
			if ( is_string( $value ) && preg_match( '/[^0-9\(\) \-\.\+]/', $value ) ) {
				return new WP_Error( 'rest_invalid_phone_number', __( 'Invalid phone number characters.', 'google-listings-and-ads' ) );
			}
			if ( empty( $value ) ) {
				return new WP_Error( 'rest_empty_phone_number', __( 'Invalid phone number.', 'google-listings-and-ads' ) );
			}

			return rest_validate_request_arg( $value, $request, $param );
		};
	}
}
