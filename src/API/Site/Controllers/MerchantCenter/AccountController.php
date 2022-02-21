<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ApiNotReady;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
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
			'mc/accounts',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_accounts_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->setup_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
		$this->register_route(
			'mc/accounts/claim-overwrite',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->overwrite_claim_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
		$this->register_route(
			'mc/accounts/switch-url',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->switch_url_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
		$this->register_route(
			'mc/connection',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connected_merchant_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->disconnect_merchant_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
		$this->register_route(
			'mc/setup',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_setup_merchant_callback(),
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
		return function( Request $request ) {
			try {
				return array_map(
					function( $account ) use ( $request ) {
						$data = $this->prepare_item_for_response( $account, $request );
						return $this->prepare_response_for_collection( $data );
					},
					$this->account->get_accounts()
				);
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback for creating or linking an account, overwriting the website claim during the claim step.
	 *
	 * @return callable
	 */
	protected function overwrite_claim_callback(): callable {
		return $this->setup_account_callback( 'overwrite_claim' );
	}

	/**
	 * Get the callback for creating or linking an account, switching the URL during the set_id step.
	 *
	 * @return callable
	 */
	protected function switch_url_callback(): callable {
		return $this->setup_account_callback( 'switch_url' );
	}

	/**
	 * Get the callback function for creating or linking an account.
	 *
	 * @param string $action Action to call while setting up account (default is normal setup).
	 * @return callable
	 */
	protected function setup_account_callback( string $action = 'setup_account' ): callable {
		return function( Request $request ) use ( $action ) {
			try {
				$account_id = absint( $request['id'] );

				if ( $account_id && 'setup_account' === $action ) {
					$this->account->use_existing_account_id( $account_id );
				}

				$account = $this->account->{$action}( $account_id );

				return $this->prepare_item_for_response( $account, $request );
			} catch ( ApiNotReady $e ) {
				return $this->get_time_to_wait_response( $e );
			} catch ( ExceptionWithResponseData $e ) {
				return new Response( $e->get_response_data( true ), $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for the connected merchant account.
	 *
	 * @return callable
	 */
	protected function get_connected_merchant_callback(): callable {
		return function() {
			return $this->account->get_connected_status();
		};
	}

	/**
	 * Get the callback function for the merchant setup status.
	 *
	 * @return callable
	 */
	protected function get_setup_merchant_callback(): callable {
		return function() {
			return $this->account->get_setup_status();
		};
	}

	/**
	 * Get the callback function for disconnecting a merchant.
	 *
	 * @return callable
	 */
	protected function disconnect_merchant_callback(): callable {
		return function() {
			$this->account->disconnect();

			return [
				'status'  => 'success',
				'message' => __( 'Merchant Center account successfully disconnected.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'         => [
				'type'              => 'number',
				'description'       => __( 'Merchant Center Account ID.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => false,
			],
			'subaccount' => [
				'type'        => 'boolean',
				'description' => __( 'Is a MCA sub account.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'name'       => [
				'type'        => 'string',
				'description' => __( 'The Merchant Center Account name.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'required'    => false,
			],
			'domain'     => [
				'type'        => 'string',
				'description' => __( 'The domain registered with the Merchant Center Account.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
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

	/**
	 * Return a 503 Response with Retry-After header and message.
	 *
	 * @param ApiNotReady $wait Exception containing the time to wait.
	 *
	 * @return Response
	 */
	private function get_time_to_wait_response( ApiNotReady $wait ): Response {
		$data = $wait->get_response_data( true );

		return new Response(
			$data,
			$wait->getCode() ?: 503,
			[
				'Retry-After' => $data['retry_after'],
			]
		);
	}

}
