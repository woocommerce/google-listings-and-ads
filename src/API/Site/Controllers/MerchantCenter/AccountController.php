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
	private const MERCHANT_ACCOUNT_CREATION_STEPS = [ 'create', 'link', 'verify', 'claim' ];

	/** @var int Status value for a pending merchant account creation step */
	private const MC_CREATION_STEP_PENDING = 0;

	/** @var int Status value for a completed merchant account creation step */
	private const MC_CREATION_STEP_DONE = 1;

	/** @var int Status value for an unsuccessful merchant account creation step */
	private const MC_CREATION_STEP_ERROR = - 1;

	/** @var int The number of seconds of delay to enforce between site verification and site claim. */
	private const MC_CLAIM_DELAY = 90;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * AccountController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->middleware = $container->get( Middleware::class );
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
					'callback'            => $this->create_or_link_account_callback(),
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
			'mc/accounts/claimwebsite',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->claimwebsite_callback(),
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
	protected function create_or_link_account_callback(): callable {
		return function( Request $request ) {
			try {
				$link_id = absint( $request['id'] );
				if ( $link_id ) {
					$this->complete_create_step( $link_id );
				}

				$response = $this->setup_merchant_account();

				return $this->prepare_item_for_response( $response, $request );
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
			'id'          => [
				'type'              => 'number',
				'description'       => __( 'Merchant Center Account ID.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => false,
			],
			'claim_delay' => [
				'type'              => 'number',
				'description'       => __( 'Seconds to wait before attempting a website claim.', 'google-listings-and-ads' ),
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
	 * Performs the steps necessary to initialize a Merchant Center sub-account.
	 * Should always resume up at the last pending or unfinished step. If the Merchant Center account
	 * has already been created, the ID is simply returned.
	 *
	 * @todo Check Google Account & Manager Accounts connected correctly before starting.
	 * @todo Include request+approve account linking process.
	 *
	 * @return array The newly created (or pre-existing) Merchant ID and the website claim delay if there is one.
	 * @throws Exception If an error occurs during any step.
	 */
	protected function setup_merchant_account(): array {
		$state       = $this->get_merchant_account_state();
		$merchant_id = intval( $this->options->get( OptionsInterface::MERCHANT_ID ) );
		$response    = [ 'id' => $merchant_id ];

		foreach ( $state as $name => &$step ) {
			if ( self::MC_CREATION_STEP_DONE === $step['status'] ) {
				continue;
			}

			try {
				switch ( $name ) {
					case 'create':
						// Just in case, don't create another merchant ID.
						if ( ! empty( $merchant_id ) ) {
							break;
						}
						$response['id']           = intval( $this->middleware->create_merchant_account() );
						$step['data']['from_mca'] = true;
						break;
					case 'link':
						// Request MCA
						// Approve MCC
						break;
					case 'verify':
						$response['claim_delay'] = $this->verify_site();
						// Only delay before claiming if not already verified.
						if ( $response['claim_delay'] ) {
							$step['data']['verify_timestamp'] = time();
						}
						break;
					case 'claim':
						continue 2;
					default:
						throw new Exception(
							sprintf(
							/* translators: 1: is an unknown step name */
								__( 'Unknown merchant account creation step %s$1', 'google-listings-and-ads' ),
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

		return $response;
	}

	/**
	 * Get callback function for claiming a website
	 *
	 * @return callable
	 */
	private function claimwebsite_callback(): callable {
		return function() {
			$state = $this->get_merchant_account_state();

			if ( ! empty( $state['claim']['status'] ) && self::MC_CREATION_STEP_DONE === $state['claim']['status'] ) {
				return [
					'status'  => 'success',
					'message' => __( 'Website already claimed.', 'google-listings-and-ads' ),
				];
			}

			// Ensure previous steps completed
			foreach ( $state as $name => $step ) {
				if ( 'claim' === $name ) {
					break;
				}
				if ( $step['status'] !== self::MC_CREATION_STEP_DONE ) {
					return new Response(
						[
							'status'  => 'error',
							'message' => __( 'Unable to claim website, previous account creation steps not completed.', 'google-listings-and-ads' ),
						],
						400
					);
				}
			}

			// Return error if not ready to be claimed
			$claim_timestamp = self::MC_CLAIM_DELAY + ( $state['verify']['data']['verify_timestamp'] ?? 0 );
			if ( time() < $claim_timestamp ) {
				$state['claim']['status']  = self::MC_CREATION_STEP_ERROR;
				$state['claim']['message'] = __( 'Please wait to execute website claim.', 'google-listings-and-ads' );
				$this->update_merchant_account_state( $state );
				return new Response(
					[
						'status'      => 'error',
						'message'     => $state['claim']['message'],
						'delay_until' => intval( $claim_timestamp ),
					],
					503,
					[
						'Retry-After' => $claim_timestamp - time(),
					]
				);
			}

			try {
				$this->container->get( Merchant::class )->claimwebsite();
			} catch ( Exception $e ) {
				$state['claim']['status']  = self::MC_CREATION_STEP_ERROR;
				$state['claim']['message'] = $e->getMessage();
				$this->update_merchant_account_state( $state );
				return new Response(
					[
						'status'  => 'error',
						'message' => $e->getMessage(),
					],
					400
				);
			}

			$state['claim']['status']  = self::MC_CREATION_STEP_DONE;
			$state['claim']['message'] = '';
			$this->update_merchant_account_state( $state );

			return [
				'status'  => 'success',
				'message' => __( 'Successfully claimed website.', 'google-listings-and-ads' ),
			];
		};
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
	 * @return int The number of seconds to delay before attempting a site claim (0 if previously verified).
	 * @throws Exception If any step of the site verification process fails.
	 */
	private function verify_site(): int {
		$site_url = apply_filters( 'woocommerce_gla_site_url', site_url() );

		// Inform of previous verification.
		if ( $this->is_site_verified() ) {
			return 0;
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

				return self::MC_CLAIM_DELAY;
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
	 * Mark the 'create' step as completed and set the Merchant ID.
	 *
	 * @param int $account_id The merchant ID to use.
	 *
	 * @throws Exception If there is already a Merchant Center ID.
	 */
	private function complete_create_step( int $account_id ): void {
		$merchant_id = intval( $this->options->get( OptionsInterface::MERCHANT_ID ) );

		if ( $merchant_id && $merchant_id !== $account_id ) {
			throw new Exception( __( 'Merchant center account already linked.', 'google-listings-and-ads' ) );
		}

		$state                               = $this->get_merchant_account_state();
		$state['create']['status']           = self::MC_CREATION_STEP_DONE;
		$state['create']['data']['from_mca'] = false;
		$this->update_merchant_account_state( $state );
		$this->middleware->link_merchant_account( $account_id );
	}
}
