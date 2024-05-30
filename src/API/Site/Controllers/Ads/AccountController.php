<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AccountController extends BaseController {

	/**
	 * Service used to access / update Ads account data.
	 *
	 * @var AccountService
	 */
	protected $account;

	/**
	 * AccountController constructor.
	 *
	 * @param RESTServer     $server
	 * @param AccountService $account
	 */
	public function __construct( RESTServer $server, AccountService $account ) {
		parent::__construct( $server );
		$this->account = $account;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/accounts',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_accounts_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->create_or_link_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			'ads/connection',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connected_ads_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->disconnect_ads_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'ads/billing-status',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_billing_status_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'ads/accepted-customer-data-terms',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_accepted_customer_data_terms_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'ads/enhanced-conversion-status',
			[
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->update_enhanced_ads_conversion_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'status' => [
							'description'       => __( 'Enhanced Conversion status.', 'google-listings-and-ads' ),
							'type'              => 'string',
							'enum'              => [ 'enabled', 'disabled', 'pending' ],
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						],
					],
				],
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_enhanced_conversion_status_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for the list accounts request.
	 *
	 * @return callable
	 */
	protected function get_accounts_callback(): callable {
		return function () {
			try {
				return new Response( $this->account->get_accounts() );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for creating or linking an account.
	 *
	 * @return callable
	 */
	protected function create_or_link_account_callback(): callable {
		return function ( Request $request ) {
			try {
				$link_id = absint( $request['id'] );
				if ( $link_id ) {
					$this->account->use_existing_account( $link_id );
				}

				$account_data = $this->account->setup_account();
				return $this->prepare_item_for_response( $account_data, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for the connected ads account.
	 *
	 * @return callable
	 */
	protected function get_connected_ads_account_callback(): callable {
		return function () {
			return $this->account->get_connected_account();
		};
	}

	/**
	 * Get the callback function for disconnecting a merchant.
	 *
	 * @return callable
	 */
	protected function disconnect_ads_account_callback(): callable {
		return function () {
			$this->account->disconnect();

			return [
				'status'  => 'success',
				'message' => __( 'Successfully disconnected.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Get the callback function for retrieving the billing setup status.
	 *
	 * @return callable
	 */
	protected function get_billing_status_callback(): callable {
		return function () {
			return $this->account->get_billing_status();
		};
	}

	/**
	 * Get the callback function for retrieving the accepted customer data terms status.
	 *
	 * @return callable
	 */
	protected function get_accepted_customer_data_terms_callback(): callable {
		return function () {
			return $this->account->get_accepted_customer_data_terms();
		};
	}

	/**
	 * Get the callback function for updating the ads enhanced conversion status.
	 *
	 * @return callable
	 */
	protected function update_enhanced_ads_conversion_callback(): callable {
		return function ( Request $request ) {
			try {
				$status = $request['status'];

				return $this->account->update_enhanced_conversion_status( $status );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for retrieving the enhanced conversion status.
	 *
	 * @return callable
	 */
	protected function get_enhanced_conversion_status_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->account->get_enhanced_conversion_status();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'          => [
				'type'              => 'number',
				'description'       => __( 'Google Ads Account ID.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => false,
			],
			'billing_url' => [
				'type'        => 'string',
				'description' => __( 'Billing Flow URL.', 'google-listings-and-ads' ),
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
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
		return 'account';
	}
}
