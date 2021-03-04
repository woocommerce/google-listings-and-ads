<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Google_Service_ShoppingContent_Account as MC_Account;
use Exception;
use Psr\Container\ContainerInterface;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
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
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * @var MerchantAccountState
	 */
	protected $account_state;

	/**
	 * @var bool Whether to perform website claim with overwrite.
	 */
	protected $overwrite_claim = false;


	/**
	 * @var bool Whether to allow changes to the existing website URL.
	 */
	protected $allow_switch_url = false;

	/**
	 * AccountController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->middleware    = $container->get( Middleware::class );
		$this->merchant      = $container->get( Merchant::class );
		$this->account_state = $container->get( MerchantAccountState::class );
		$this->container     = $container;
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
					'callback'            => $this->set_account_id_callback(),
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
					$this->middleware->get_merchant_ids()
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
		return function( Request $request ) {
			$state               = $this->account_state->get( false );
			$overwrite_necessary = ! empty( $state['claim']['data']['overwrite_required'] );
			$claim_status        = $state['claim']['status'] ?? MerchantAccountState::STEP_PENDING;
			if ( MerchantAccountState::STEP_DONE === $claim_status || ! $overwrite_necessary ) {
				return new Response(
					[ 'message' => __( 'Attempting invalid claim overwrite.', 'google-listings-and-ads' ) ],
					400
				);
			}

			$this->overwrite_claim = true;
			return $this->set_account_id( $request );
		};
	}

	/**
	 * Get the callback for creating or linking an account, switching the URL during the set_id step.
	 *
	 * @return callable
	 */
	protected function switch_url_callback(): callable {
		return function( Request $request ) {
			$state            = $this->account_state->get();
			$switch_necessary = ! empty( $state['set_id']['data']['old_url'] );
			$set_id_status    = $state['set_id']['status'] ?? MerchantAccountState::STEP_PENDING;
			if ( empty( $request['id'] ) || MerchantAccountState::STEP_DONE === $set_id_status || ! $switch_necessary ) {
				return new Response(
					[ 'message' => __( 'Attempting invalid URL switch.', 'google-listings-and-ads' ) ],
					400
				);
			}

			$this->allow_switch_url = true;
			return $this->set_account_id( $request );
		};
	}

	/**
	 * Get the callback function for creating or linking an account.
	 *
	 * @return callable
	 */
	protected function set_account_id_callback(): callable {
		return function( Request $request ) {
			return $this->set_account_id( $request );
		};
	}
	/**
	 * Get the callback function for the connected merchant account.
	 *
	 * @return callable
	 */
	protected function get_connected_merchant_callback(): callable {
		return function() {
			return $this->middleware->get_connected_merchant();
		};
	}

	/**
	 * Get the callback function for disconnecting a merchant.
	 *
	 * @return callable
	 */
	protected function disconnect_merchant_callback(): callable {
		return function() {
			$this->middleware->disconnect_merchant();

			$this->account_state->update( [] );
			$this->options->delete( OptionsInterface::SITE_VERIFICATION );

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
	 * Run the process for setting up a Merchant Center account (sub-account or standalone).
	 *
	 * @param Request $request
	 *
	 * @return array|Response The account ID if setup has completed, or an error if not.
	 */
	protected function set_account_id( Request $request ) {
		try {
			$link_id = absint( $request['id'] );
			if ( $link_id ) {
				$this->use_existing_account_id( $link_id );
			}

			$response = $this->setup_merchant_account();

			return is_a( $response, Response::class ) ? $response : $this->prepare_item_for_response( $response, $request );
		} catch ( ExceptionWithResponseData $e ) {
			return new Response( $e->get_response_data( true ), $e->getCode() ?: 400 );
		} catch ( Exception $e ) {
			return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
		}
	}

	/**
	 * Performs the steps necessary to initialize a Merchant Center sub-account.
	 * Should always resume up at the last pending or unfinished step. If the Merchant Center account
	 * has already been created, the ID is simply returned.
	 *
	 * @todo Check Google Account & Manager Accounts connected correctly before starting.
	 * @todo Include request+approve account linking process.
	 *
	 * @return array|Response The newly created (or pre-existing) Merchant ID or the retry delay.
	 * @throws Exception If an error occurs during any step.
	 */
	protected function setup_merchant_account() {
		$state       = $this->account_state->get();
		$merchant_id = intval( $this->options->get( OptionsInterface::MERCHANT_ID ) );

		foreach ( $state as $name => &$step ) {
			if ( MerchantAccountState::STEP_DONE === $step['status'] ) {
				continue;
			}

			if ( 'link' === $name || 'claim' === $name ) {
				$time_to_wait = $this->account_state->get_seconds_to_wait_after_created();
				if ( $time_to_wait ) {
					return $this->get_time_to_wait_response( $time_to_wait );
				}
			}

			try {
				switch ( $name ) {
					case 'set_id':
						// Just in case, don't create another merchant ID.
						if ( ! empty( $merchant_id ) ) {
							break;
						}
						$merchant_id                       = $this->middleware->create_merchant_account();
						$step['data']['from_mca']          = true;
						$step['data']['created_timestamp'] = time();
						$this->merchant->set_id( $merchant_id );
						break;
					case 'verify':
						$this->verify_site();
						break;
					case 'link':
						$this->middleware->link_merchant_to_mca();
						break;
					case 'claim':
						// At this step, the website URL is assumed to be correct.
						// If the URL is already claimed, no claim should be attempted.
						if ( $this->merchant->get_accountstatus( $merchant_id )->getWebsiteClaimed() ) {
							break;
						}

						if ( $this->overwrite_claim ) {
							$this->middleware->claim_merchant_website( true );
						} else {
							$this->merchant->claimwebsite();
						}
						break;
					default:
						throw new Exception(
							sprintf(
							/* translators: 1: is a string representing an unknown step name */
								__( 'Unknown merchant account creation step %1$s', 'google-listings-and-ads' ),
								$name
							)
						);
				}
				$step['status']  = MerchantAccountState::STEP_DONE;
				$step['message'] = '';
				$this->account_state->update( $state );
			} catch ( Exception $e ) {
				$step['status']  = MerchantAccountState::STEP_ERROR;
				$step['message'] = $e->getMessage();

				if ( 'claim' === $name && 403 === $e->getCode() ) {
					$data = [
						'website_url' => $this->strip_url_protocol(
							apply_filters( 'woocommerce_gla_site_url', site_url() )
						),
					];

					if ( $state['set_id']['data']['from_mca'] ?? true ) {
						$step['data']['overwrite_required'] = true;
						$e                                  = new ExceptionWithResponseData( $e->getMessage(), $e->getCode(), null, $data );
					} else {
						throw new ExceptionWithResponseData(
							__( 'Unable to claim website URL with this Merchant Center Account.', 'google-listings-and-ads' ),
							406,
							null,
							$data
						);
					}
				} elseif ( 'link' === $name && 401 === $e->getCode() ) {
					$state['set_id']['data']['created_timestamp'] = time();
					$this->account_state->update( $state );
					return $this->get_time_to_wait_response( MerchantAccountState::MC_DELAY_AFTER_CREATE );
				}

				$this->account_state->update( $state );
				throw $e;
			}
		}

		return [ 'id' => $merchant_id ];
	}

	/**
	 * Performs the three-step process of verifying the current site:
	 * 1. Retrieves the meta tag with the verification token.
	 * 2. Enables the meta tag in the head of the store.
	 * 3. Instructs the Site Verification API to verify the meta tag.
	 *
	 * @return bool True if the site has been (or already was) verified for the connected Google account.
	 * @throws Exception If any step of the site verification process fails.
	 */
	private function verify_site(): bool {
		$site_url = apply_filters( 'woocommerce_gla_site_url', site_url() );

		// Inform of previous verification.
		if ( $this->account_state->is_site_verified() ) {
			return true;
		}

		// Retrieve the meta tag with verification token.
		/** @var SiteVerification $site_verification */
		$site_verification = $this->container->get( SiteVerification::class );
		try {
			$meta_tag = $site_verification->get_token( $site_url );
		} catch ( Exception $e ) {
			do_action( 'gla_site_verify_failure', [ 'step' => 'token' ] );
			throw $e;
		}

		// Store the meta tag in the options table and mark as unverified.
		$site_verification_options = [
			'verified' => $site_verification::VERIFICATION_STATUS_UNVERIFIED,
			'meta_tag' => $meta_tag,
		];
		$this->options->update(
			OptionsInterface::SITE_VERIFICATION,
			$site_verification_options
		);

		// Attempt verification.
		try {
			if ( $site_verification->insert( $site_url ) ) {
				$site_verification_options['verified'] = $site_verification::VERIFICATION_STATUS_VERIFIED;
				$this->options->update( OptionsInterface::SITE_VERIFICATION, $site_verification_options );
				do_action( 'gla_site_verify_success', [] );

				return true;
			}
		} catch ( Exception $e ) {
			do_action( 'gla_site_verify_failure', [ 'step' => 'meta-tag' ] );

			throw $e;
		}

		// Should never reach this point.
		do_action( 'gla_site_verify_failure', [ 'step' => 'unknown' ] );

		throw new Exception( __( 'Site verification failed.', 'google-listings-and-ads' ) );
	}



	/**
	 * Use an existing MC account. Mark the 'set_id' step as done, update the MC account's website URL,
	 * and sets the Merchant ID.
	 *
	 * @param int $account_id The merchant ID to use.
	 *
	 * @throws Exception If there is already a Merchant Center ID or the website can't be configured correctly.
	 * @throws ExceptionWithResponseData If there's a website URL conflict.
	 */
	private function use_existing_account_id( int $account_id ): void {
		$merchant_id = intval( $this->options->get( OptionsInterface::MERCHANT_ID ) );
		if ( $merchant_id && $merchant_id !== $account_id ) {
			throw new Exception(
				sprintf(
					/* translators: 1: is a numeric account ID */
					__( 'Merchant Center sub-account %1$d already created.', 'google-listings-and-ads' ),
					$merchant_id
				)
			);
		}

		$state = $this->account_state->get();

		// Don't do anything if this step was already finished.
		if ( MerchantAccountState::STEP_DONE === $state['set_id']['status'] ) {
			return;
		}

		// Make sure the existing account has the correct website URL (or fail).
		$this->maybe_add_merchant_center_website_url( $account_id, apply_filters( 'woocommerce_gla_site_url', site_url() ) );

		// Maybe the existing account is sub-account!
		$state                               = $this->account_state->get();
		$state['set_id']['data']['from_mca'] = false;
		foreach ( $this->middleware->get_merchant_ids() as $existing_account ) {
			if ( $existing_account['id'] === $account_id ) {
				$state['set_id']['data']['from_mca'] = $existing_account['subaccount'];
				break;
			}
		}

		$this->middleware->link_merchant_account( $account_id );
		$this->merchant->set_id( $account_id );
		$state['set_id']['status'] = MerchantAccountState::STEP_DONE;
		$this->account_state->update( $state );
	}

	/**
	 * Ensure the Merchant Center account's Website URL matches the site URL. Update an empty value or
	 * a different, unclaimed URL value. Throw a 409 exception if a different, claimed URL is found.
	 *
	 * @param int    $merchant_id      The Merchant Center account to update
	 * @param string $site_website_url The new website URL
	 *
	 * @return bool True if the Merchant Center website URL matches the provided URL (updated or already set).
	 * @throws Exception If the Merchant Center account can't be retrieved.
	 * @throws ExceptionWithResponseData If the account website URL doesn't match the given URL.
	 */
	private function maybe_add_merchant_center_website_url( int $merchant_id, string $site_website_url ): bool {
		/** @var MC_Account $mc_account */
		$mc_account = $this->merchant->get_account( $merchant_id );

		$account_website_url = $mc_account->getWebsiteUrl();

		if ( untrailingslashit( $site_website_url ) !== untrailingslashit( $account_website_url ) ) {

			$is_website_claimed = $this->merchant->get_accountstatus( $merchant_id )->getWebsiteClaimed();

			if ( ! empty( $account_website_url ) && $is_website_claimed && ! $this->allow_switch_url ) {
				$state                              = $this->account_state->get();
				$state['set_id']['data']['old_url'] = $account_website_url;
				$state['set_id']['status']          = MerchantAccountState::STEP_ERROR;
				$this->account_state->update( $state );

				$clean_account_website_url = $this->strip_url_protocol( $account_website_url );
				$clean_site_website_url    = $this->strip_url_protocol( $site_website_url );

				throw new ExceptionWithResponseData(
					sprintf(
					/* translators: 1: is a website URL (without the protocol) */
						__( 'This Merchant Center account already has a verified and claimed URL, %1$s', 'google-listings-and-ads' ),
						$clean_account_website_url
					),
					409,
					null,
					[
						'id'          => $merchant_id,
						'claimed_url' => $clean_account_website_url,
						'new_url'     => $clean_site_website_url,
					]
				);
			}

			$mc_account->setWebsiteUrl( $site_website_url );
			$this->merchant->update_account( $mc_account );
		}

		return true;
	}

	/**
	 * Generate a 503 Response with Retry-After header and message.
	 *
	 * @param int $time_to_wait The time to indicate
	 *
	 * @return Response
	 */
	private function get_time_to_wait_response( int $time_to_wait ): Response {
		return new Response(
			[
				'retry_after' => $time_to_wait,
				'message'     => __( 'Please retry after the indicated number of seconds to complete the account setup process.', 'google-listings-and-ads' ),
			],
			503,
			[
				'Retry-After' => $time_to_wait,
			]
		);
	}

	/**
	 * Removes the protocol (http:// or https://) and trailing slash from the provided URL.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private function strip_url_protocol( string $url ): string {
		return preg_replace( '#^https?://#', '', untrailingslashit( $url ) );
	}
}
