<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use Psr\Container\ContainerInterface;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AccountController extends BaseOptionsController {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * @var AdsAccountState
	 */
	protected $account_state;

	/**
	 * BaseController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->middleware    = $container->get( Middleware::class );
		$this->account_state = $container->get( AdsAccountState::class );
		$this->container     = $container;
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
	}

	/**
	 * Get the callback function for the list accounts request.
	 *
	 * @return callable
	 */
	protected function get_accounts_callback(): callable {
		return function() {
			try {
				return new Response( $this->middleware->get_ads_account_ids() );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for creating or linking an account.
	 *
	 * @return callable
	 */
	protected function create_or_link_account_callback(): callable {
		return function( Request $request ) {
			try {
				$link_id = absint( $request['id'] );
				if ( $link_id ) {
					$this->use_existing_account( $link_id );
				}

				$account = $this->setup_account();
				return $this->prepare_item_for_response( $account, $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for the connected ads account.
	 *
	 * @return callable
	 */
	protected function get_connected_ads_account_callback(): callable {
		return function() {
			return $this->middleware->get_connected_ads_account();
		};
	}

	/**
	 * Get the callback function for disconnecting a merchant.
	 *
	 * @return callable
	 */
	protected function disconnect_ads_account_callback(): callable {
		return function() {
			$this->middleware->disconnect_ads_account();
			$this->account_state->update( [] );

			return [
				'status'  => 'success',
				'message' => __( 'Successfully disconnected.', 'google-listings-and-ads' ),
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
			'id' => [
				'type'              => 'number',
				'description'       => __( 'Google Ads Account ID.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => false,
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
	 * Use an existing Ads account. Mark the 'set_id' step as done and sets the Ads ID.
	 *
	 * @param int $account_id The Ads account ID to use.
	 *
	 * @throws Exception If there is already an Ads account ID.
	 */
	private function use_existing_account( int $account_id ) {
		$ads_id = $this->options->get( OptionsInterface::ADS_ID );
		if ( $ads_id && $ads_id !== $account_id ) {
			throw new Exception(
				/* translators: 1: is a numeric account ID */
				sprintf( __( 'Ads account %1$d already connected.', 'google-listings-and-ads' ), $ads_id )
			);
		}

		$state = $this->account_state->get();

		// Don't do anything if this step was already finished.
		if ( AdsAccountState::STEP_DONE === $state['set_id']['status'] ) {
			return;
		}

		$this->middleware->link_ads_account( $account_id );
		$state['set_id']['status'] = AdsAccountState::STEP_DONE;
		$this->account_state->update( $state );
	}

	/**
	 * Performs the steps necessary to setup an ads account.
	 * Should always resume up at the last pending or unfinished step.
	 * If the Ads account has already been created, the ID is simply returned.
	 *
	 * @return array The newly created (or pre-existing) Ads ID.
	 * @throws Exception If an error occurs during any step.
	 */
	protected function setup_account(): array {
		$state  = $this->account_state->get();
		$ads_id = $this->options->get( OptionsInterface::ADS_ID );

		foreach ( $state as $name => &$step ) {
			if ( AdsAccountState::STEP_DONE === $step['status'] ) {
				continue;
			}

			try {
				switch ( $name ) {
					case 'set_id':
						// Just in case, don't create another Ads ID.
						if ( ! empty( $ads_id ) ) {
							break;
						}
						$ads_id = $this->middleware->create_ads_account();
						break;
					default:
						throw new Exception(
							/* translators: 1: is a string representing an unknown step name */
							sprintf( __( 'Unknown ads account creation step %1$s', 'google-listings-and-ads' ), $name )
						);
				}
				$step['status']  = AdsAccountState::STEP_DONE;
				$step['message'] = '';
				$this->account_state->update( $state );
			} catch ( Exception $e ) {
				$step['status']  = AdsAccountState::STEP_ERROR;
				$step['message'] = $e->getMessage();
				$this->account_state->update( $state );
				throw $e;
			}
		}

		return [ 'id' => $ads_id ];
	}
}
