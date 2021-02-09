<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
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
	 * @var string[]
	 */
	private const MERCHANT_ACCOUNT_CREATION_STEPS = [ 'set_id', 'verify', 'link', 'claim' ];

	/** @var int Status value for a pending merchant account creation step */
	private const MC_CREATION_STEP_PENDING = 0;

	/** @var int Status value for a completed merchant account creation step */
	private const MC_CREATION_STEP_DONE = 1;

	/** @var int Status value for an unsuccessful merchant account creation step */
	private const MC_CREATION_STEP_ERROR = - 1;

	/** @var int The number of seconds of delay to enforce between site verification and site claim. */
	private const MC_DELAY_AFTER_CREATE = 90;

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
	 * AccountController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->middleware = $container->get( Middleware::class );
		$this->merchant   = $container->get( Merchant::class );
		$this->set_options_object( $container->get( OptionsInterface::class ) );
		$this->container = $container;
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
		return function() {
			try {
				return new Response( $this->middleware->get_merchant_ids() );
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
	protected function set_account_id_callback(): callable {
		return function( Request $request ) {
			try {
				$link_id = absint( $request['id'] );
				if ( $link_id ) {
					$this->use_standalone_account_id( $link_id );
				}

				$response = $this->setup_merchant_account();

				return is_a( $response, Response::class ) ? $response : $this->prepare_item_for_response( $response, $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
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
				'description'       => __( 'Merchant Center Account ID.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
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
		$state       = $this->get_merchant_account_state();
		$merchant_id = intval( $this->options->get( OptionsInterface::MERCHANT_ID ) );

		foreach ( $state as $name => &$step ) {
			if ( self::MC_CREATION_STEP_DONE === $step['status'] ) {
				continue;
			}

			if ( 'link' === $name || 'claim' === $name ) {
				$time_to_wait = $this->get_seconds_to_wait_after_created();
				if ( $time_to_wait ) {
					return new Response(
						[
							'retry_after' => $time_to_wait,
							'message'     => __( 'Please retry after the indicated number of seconds.', 'google-listings-and-ads' ),
						],
						503,
						[
							'Retry-After' => $time_to_wait,
						]
					);
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
						$this->merchant->claimwebsite();
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
				$step['status']  = self::MC_CREATION_STEP_DONE;
				$step['message'] = '';
				$this->update_merchant_account_state( $state );
			} catch ( Exception $e ) {
				$step['status']  = self::MC_CREATION_STEP_ERROR;
				$step['message'] = $e->getMessage();
				$this->update_merchant_account_state( $state );
				throw $e;
			}
		}

		return [ 'id' => $merchant_id ];
	}

	/**
	 * Retrieve or initialize the MERCHANT_ACCOUNT_STATE Option.
	 *
	 * @param bool $initialize_if_not_found True to initialize and option array of steps.
	 *
	 * @return array The MC creation steps and statuses.
	 */
	private function get_merchant_account_state( bool $initialize_if_not_found = true ): array {
		$state = $this->options->get( OptionsInterface::MERCHANT_ACCOUNT_STATE );
		if ( empty( $state ) && $initialize_if_not_found ) {
			$state = [];
			foreach ( self::MERCHANT_ACCOUNT_CREATION_STEPS as $step ) {
				$state[ $step ] = [
					'status'  => self::MC_CREATION_STEP_PENDING,
					'message' => '',
					'data'    => [],
				];
			}
			$this->update_merchant_account_state( $state );
		}

		return $state;
	}

	/**
	 * Update the MERCHANT_ACCOUNT_STATE Option.
	 *
	 * @param array $state
	 */
	private function update_merchant_account_state( array $state ) {
		$this->options->update( OptionsInterface::MERCHANT_ACCOUNT_STATE, $state );
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
		if ( $this->is_site_verified() ) {
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
	 * Determine whether the site has already been verified.
	 *
	 * @return bool True if the site is marked as verified.
	 */
	private function is_site_verified(): bool {
		$current_options = $this->options->get( OptionsInterface::SITE_VERIFICATION );

		return ! empty( $current_options['verified'] ) && $this->container->get( SiteVerification::class )::VERIFICATION_STATUS_VERIFIED === $current_options['verified'];
	}


	/**
	 * Mark the 'set_id' step as completed and set the Merchant ID.
	 *
	 * @param int $account_id The merchant ID to use.
	 *
	 * @throws Exception If there is already a Merchant Center ID or the website can't be configured correctly.
	 */
	private function use_standalone_account_id( int $account_id ): void {
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

		// Make sure the standalone account has the correct website URL (or fail).
		$this->maybe_add_merchant_center_website_url( $account_id, apply_filters( 'woocommerce_gla_site_url', site_url() ) );

		$state                               = $this->get_merchant_account_state();
		$state['set_id']['status']           = self::MC_CREATION_STEP_DONE;
		$state['set_id']['data']['from_mca'] = false;
		$this->update_merchant_account_state( $state );
		$this->middleware->link_merchant_account( $account_id );
		$this->merchant->set_id( $account_id );
	}

	/**
	 * Ensure the Merchant Center account's Website URL matches the site URL, updating an empty value if
	 * necessary. Fails if the Merchant Center account has a different Website URL.
	 *
	 * @param int    $merchant_id      The Merchant Center account to update
	 * @param string $site_website_url The new website URL
	 *
	 * @return bool True if the Merchant Center website URL matches the provided URL (updated or already set).
	 * @throws Exception If there's an error updating the website URL.
	 */
	private function maybe_add_merchant_center_website_url( int $merchant_id, string $site_website_url ): bool {
		/** @var MC_Account $mc_account */
		$mc_account = $this->merchant->get_account( $merchant_id );

		$account_website_url = $mc_account->getWebsiteUrl();

		if ( empty( $account_website_url ) ) {
			$mc_account->setWebsiteUrl( $site_website_url );
			$this->merchant->update_account( $mc_account );
		} elseif ( $site_website_url !== $account_website_url ) {
			throw new Exception( __( 'Merchant Center account has a different website URL.', 'google-listings-and-ads' ) );
		}

		return true;
	}

	/**
	 * Calculate the number of seconds to wait after creating a sub-account and
	 * before operating on the new sub-account (MCA link and website claim).
	 *
	 * @return int
	 */
	private function get_seconds_to_wait_after_created(): int {
		$state = $this->get_merchant_account_state( false );

		$created_timestamp = $state['set_id']['data']['created_timestamp'] ?? 0;
		$seconds_elapsed   = time() - $created_timestamp;

		return max( 0, self::MC_DELAY_AFTER_CREATE - $seconds_elapsed );
	}
}
