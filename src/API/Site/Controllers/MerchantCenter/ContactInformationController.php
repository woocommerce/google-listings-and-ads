<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\ContactInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\AddressUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PhoneNumber;
use Exception;
use Google\Service\ShoppingContent\AccountAddress;
use Google\Service\ShoppingContent\AccountBusinessInformation;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ContactInformationController
 *
 * @since 1.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ContactInformationController extends BaseOptionsController {

	/**
	 * @var ContactInformation $contact_information
	 */
	protected $contact_information;

	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * @var AddressUtility
	 */
	protected $address_utility;

	/**
	 * ContactInformationController constructor.
	 *
	 * @param RESTServer         $server
	 * @param ContactInformation $contact_information
	 * @param Settings           $settings
	 * @param AddressUtility     $address_utility
	 */
	public function __construct( RESTServer $server, ContactInformation $contact_information, Settings $settings, AddressUtility $address_utility ) {
		parent::__construct( $server );
		$this->contact_information = $contact_information;
		$this->settings            = $settings;
		$this->address_utility     = $address_utility;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/contact-information',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_contact_information_endpoint_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_contact_information_endpoint_edit_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_update_args(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get a callback for the contact information endpoint.
	 *
	 * @return callable
	 */
	protected function get_contact_information_endpoint_read_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->get_contact_information_response(
					$this->contact_information->get_contact_information(),
					$request
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get a callback for the edit contact information endpoint.
	 *
	 * @return callable
	 */
	protected function get_contact_information_endpoint_edit_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->get_contact_information_response(
					$this->contact_information->update_address_based_on_store_settings(),
					$request
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the schema for contact information endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'                        => [
				'type'              => 'integer',
				'description'       => __( 'The Merchant Center account ID.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'phone_number'              => [
				'type'        => 'string',
				'description' => __( 'The phone number associated with the Merchant Center account.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'phone_verification_status' => [
				'type'        => 'string',
				'description' => __( 'The verification status of the phone number associated with the Merchant Center account.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'enum'        => [ 'verified', 'unverified' ],
			],
			'mc_address'                => [
				'type'        => 'object',
				'description' => __( 'The address associated with the Merchant Center account.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'properties'  => $this->get_address_schema(),
			],
			'wc_address'                => [
				'type'        => 'object',
				'description' => __( 'The WooCommerce store address.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'properties'  => $this->get_address_schema(),
			],
			'is_mc_address_different'   => [
				'type'        => 'boolean',
				'description' => __( 'Whether the Merchant Center account address is different than the WooCommerce store address.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'wc_address_errors'         => [
				'type'        => 'array',
				'description' => __( 'The errors associated with the WooCommerce address', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
		];
	}

	/**
	 * Get the schema for addresses returned by the contact information endpoints.
	 *
	 * @return array[]
	 */
	protected function get_address_schema(): array {
		return [
			'street_address' => [
				'description' => __( 'Street-level part of the address.', 'google-listings-and-ads' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
			],
			'locality'       => [
				'description' => __( 'City, town or commune. May also include dependent localities or sublocalities (e.g. neighborhoods or suburbs).', 'google-listings-and-ads' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
			],
			'region'         => [
				'description' => __( 'Top-level administrative subdivision of the country. For example, a state like California ("CA") or a province like Quebec ("QC").', 'google-listings-and-ads' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
			],
			'postal_code'    => [
				'description' => __( 'Postal code or ZIP (e.g. "94043").', 'google-listings-and-ads' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
			],
			'country'        => [
				'description' => __( 'CLDR country code (e.g. "US").', 'google-listings-and-ads' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
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
			'context' => $this->get_context_param( [ 'default' => 'view' ] ),
		];
	}

	/**
	 * Get the prepared REST response with Merchant Center account ID and contact information.
	 *
	 * @param AccountBusinessInformation|null $contact_information
	 * @param Request                         $request
	 *
	 * @return Response
	 */
	protected function get_contact_information_response( ?AccountBusinessInformation $contact_information, Request $request ): Response {
		$phone_number              = null;
		$phone_verification_status = null;
		$mc_address                = null;
		$wc_address                = null;
		$is_address_diff           = false;

		if ( $this->settings->get_store_address() instanceof AccountAddress ) {
			$wc_address      = $this->settings->get_store_address();
			$is_address_diff = true;
		}

		if ( $contact_information instanceof AccountBusinessInformation ) {
			if ( ! empty( $contact_information->getPhoneNumber() ) ) {
				try {
					$phone_number              = PhoneNumber::cast( $contact_information->getPhoneNumber() )->get();
					$phone_verification_status = strtolower( $contact_information->getPhoneVerificationStatus() );
				} catch ( InvalidValue $exception ) {
					// log and fail silently
					do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
				}
			}

			if ( $contact_information->getAddress() instanceof AccountAddress ) {
				$mc_address      = $contact_information->getAddress();
				$is_address_diff = true;
			}

			if ( null !== $mc_address && null !== $wc_address ) {
				$is_address_diff = ! $this->address_utility->compare_addresses( $contact_information->getAddress(), $this->settings->get_store_address() );
			}
		}

		$wc_address_errors = $this->settings->wc_address_errors( $wc_address );

		return $this->prepare_item_for_response(
			[
				'id'                        => $this->options->get_merchant_id(),
				'phone_number'              => $phone_number,
				'phone_verification_status' => $phone_verification_status,
				'mc_address'                => self::serialize_address( $mc_address ),
				'wc_address'                => self::serialize_address( $wc_address ),
				'is_mc_address_different'   => $is_address_diff,
				'wc_address_errors'         => $wc_address_errors,
			],
			$request
		);
	}

	/**
	 * @param AccountAddress|null $address
	 *
	 * @return array|null
	 */
	protected static function serialize_address( ?AccountAddress $address ): ?array {
		if ( null === $address ) {
			return null;
		}

		return [
			'street_address' => $address->getStreetAddress(),
			'locality'       => $address->getLocality(),
			'region'         => $address->getRegion(),
			'postal_code'    => $address->getPostalCode(),
			'country'        => $address->getCountry(),
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
		return 'merchant_center_contact_information';
	}
}
