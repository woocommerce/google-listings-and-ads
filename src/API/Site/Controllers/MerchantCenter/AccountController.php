<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ControllerTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Exception;
use Psr\Container\ContainerInterface;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class AccountController extends BaseOptionsController {

	use ControllerTrait;

	/**
	 * @var string[]
	 */
	private const MERCHANT_ACCOUNT_CREATION_STEPS = [ 'create', 'link', 'verify', 'claim' ];

	/** @var int Status value for a pending merchant account creation step*/
	private const MC_CREATION_STEP_PENDING = 0;

	/** @var int Status value for a completed merchant account creation step*/
	private const MC_CREATION_STEP_DONE = 1;

	/** @var int Status value for an unsuccessful merchant account creation step*/
	private const MC_CREATION_STEP_ERROR = - 1;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Middleware
	 */
	protected $middleware;


	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * AccountController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->options = $container->get( OptionsInterface::class );
		parent::__construct( $container->get( RESTServer::class ), $this->options );
		$this->middleware = $container->get( Middleware::class );
		$this->container  = $container;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
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
					'args'                => $this->get_item_schema(),
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
		return function () {
			try {
				return new WP_REST_Response( $this->middleware->get_merchant_ids() );
			} catch ( Exception $e ) {
				return new WP_REST_Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for creating or linking an account.
	 *
	 * @return callable
	 */
	protected function create_or_link_account_callback(): callable {
		return function ( WP_REST_Request $request ) {
			try {
				$link_id = absint( $request['id'] );

				if ( $link_id ) {
					$account_id = $this->middleware->link_merchant_account( $link_id );
				} else {
					$account_id = $this->setup_merchant_account();
				}

				return $this->prepare_item_for_response( [ 'id' => $account_id ] );
			} catch ( Exception $e ) {
				return new WP_REST_Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
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
	protected function get_item_schema(): array {
		return [
			'id' => [
				'type'              => 'number',
				'description'       => __( 'Merchant Center Account ID.', 'google-listings-and-ads' ),
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
	protected function get_item_schema_name(): string {
		return 'account';
	}

	/**
	 * Performs the steps necessary to initialize a Merchant Center sub-account.
	 * Should always resume up at the last pending or unfinished step.
	 *
	 * @return int The newly created Merchant ID.
	 * @throws Exception If an error occurs during any step.
	 */
	protected function setup_merchant_account(): int {
		$state              = $this->get_merchant_account_state();
		$delay_after_verify = false;

		foreach ( $state as &$step ) {
			if ( self::MC_CREATION_STEP_DONE === $step['status'] ) {
				if ( 'create' === $step['name'] ) {
					$merchant_id = intval( $this->container->get( 'merchant_id' ) );
				}
				continue;
			}

			try {
				switch ( $step['name'] ) {
					case 'create':
						// Just in case
						if ( ! empty( $merchant_id ) ) {
							break;
						}
						$merchant_id = $this->middleware->create_merchant_account();
						break;
					case 'link':
						// Request MCA
						// Approve MCC
						break;
					case 'verify':
						$this->verify_site();
						$delay_after_verify = true;
						break;
					case 'claim':
						// Time necessary between verify and claim :shrug:
						if ( $delay_after_verify ) {
							$sl = 65;
							usleep( $sl * 1000000 );
						}
						$this->middleware->claim_merchant_website();
						break;
					default:
						throw new Exception(
							sprintf(
							/* translators: 1: is an unknown step name */
								__( 'Unknown merchant account creation step %s$1', 'google-listings-and-ads' ),
								$step['name']
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

		return intval( $merchant_id );
	}

	/**
	 * Retrieve or initialize the MERCHANT_ACCOUNT_STATE Option.
	 *
	 * @return array The MC creation steps and statuses.
	 */
	private function get_merchant_account_state(): array {
		$state = $this->options->get( OptionsInterface::MERCHANT_ACCOUNT_STATE );
		if ( empty( $state ) ) {
			$state = [];
			foreach ( self::MERCHANT_ACCOUNT_CREATION_STEPS as $step ) {
				array_push(
					$state,
					[
						'name'    => $step,
						'status'  => self::MC_CREATION_STEP_PENDING,
						'message' => '',
					]
				);
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
	 * @return bool True if the entire process succeeds and the site is verified for the user.
	 * @throws Exception If any step of the site verification process fails.
	 */
	private function verify_site() {
		$site_url = site_url();

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
			do_action( $this->get_slug() . '_site_verify_failure', [ 'step' => 'token' ] );
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
				do_action( $this->get_slug() . '_site_verify_success', [] );

				return true;
			}
		} catch ( Exception $e ) {
			do_action( $this->get_slug() . '_site_verify_failure', [ 'step' => 'meta-tag' ] );

			throw $e;
		}

		// Should never reach this point.
		do_action( $this->get_slug() . '_site_verify_failure', [ 'step' => 'unknown' ] );

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

}
