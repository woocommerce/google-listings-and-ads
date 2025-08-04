<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\Jetpack\Connection\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ApiNotReady;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupSyncedProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;
use Exception;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountService
 *
 * Container used to access:
 * - Ads
 * - AdsAccountState
 * - JobRepository
 * - Merchant
 * - MerchantCenterService
 * - MerchantIssueTable
 * - MerchantStatuses
 * - Middleware
 * - SiteVerification
 * - ShippingRateTable
 * - ShippingTimeTable
 *
 * @since 1.12.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class AccountService implements ContainerAwareInterface, OptionsAwareInterface, Service {

	use ContainerAwareTrait;
	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * @var MerchantAccountState
	 */
	protected $state;

	/**
	 * Perform a website claim with overwrite.
	 *
	 * @var bool
	 */
	protected $overwrite_claim = false;

	/**
	 * Allow switching the existing website URL.
	 *
	 * @var bool
	 */
	protected $allow_switch_url = false;

	/**
	 * AccountService constructor.
	 *
	 * @param MerchantAccountState $state
	 */
	public function __construct( MerchantAccountState $state ) {
		$this->state = $state;
	}

	/**
	 * Get all Merchant Accounts associated with the connected account.
	 *
	 * @return array
	 * @throws Exception When an API error occurs.
	 */
	public function get_accounts(): array {
		return $this->container->get( Middleware::class )->get_merchant_accounts();
	}

	/**
	 * Use an existing MC account. Mark the 'set_id' step as done, update the MC account's website URL,
	 * and sets the Merchant ID.
	 *
	 * @param int $account_id The merchant ID to use.
	 *
	 * @throws ExceptionWithResponseData If there's a website URL conflict, or account data can't be retrieved.
	 */
	public function use_existing_account_id( int $account_id ): void {
		// Reset the process if the provided ID isn't the same as the one stored in options.
		$merchant_id = $this->options->get_merchant_id();
		if ( $merchant_id && $merchant_id !== $account_id ) {
			$this->reset_account_setup();
		}

		$state = $this->state->get();

		// Don't do anything if this step was already finished.
		if ( MerchantAccountState::STEP_DONE === $state['set_id']['status'] ) {
			return;
		}

		try {
			// Make sure the existing account has the correct website URL (or fail).
			$this->maybe_add_merchant_center_url( $account_id );

			// Re-fetch state as it might have changed.
			$state      = $this->state->get();
			$middleware = $this->container->get( Middleware::class );

			// Maybe the existing account is a sub-account!
			$state['set_id']['data']['from_mca'] = false;
			foreach ( $middleware->get_merchant_accounts() as $existing_account ) {
				if ( $existing_account['id'] === $account_id ) {
					$state['set_id']['data']['from_mca'] = $existing_account['subaccount'];
					break;
				}
			}

			$middleware->link_merchant_account( $account_id );
			$state['set_id']['status'] = MerchantAccountState::STEP_DONE;
			$this->state->update( $state );
		} catch ( ExceptionWithResponseData $e ) {
			throw $e;
		} catch ( Exception $e ) {
			throw $this->prepare_exception( $e->getMessage(), [], $e->getCode() );
		}
	}

	/**
	 * Run the process for setting up a Merchant Center account (sub-account or standalone).
	 *
	 * @param int $account_id
	 *
	 * @return array The account ID if setup has completed.
	 * @throws ExceptionWithResponseData When the account is already connected or a setup error occurs.
	 */
	public function setup_account( int $account_id ) {
		// Reset the process if the provided ID isn't the same as the one stored in options.
		$merchant_id = $this->options->get_merchant_id();
		if ( $merchant_id && $merchant_id !== $account_id ) {
			$this->reset_account_setup();
		}

		try {
			return $this->setup_account_steps();
		} catch ( ExceptionWithResponseData | ApiNotReady $e ) {
			throw $e;
		} catch ( Exception $e ) {
			throw $this->prepare_exception( $e->getMessage(), [], $e->getCode() );
		}
	}

	/**
	 * Create or link an account, switching the URL during the set_id step.
	 *
	 * @param int $account_id
	 *
	 * @return array
	 * @throws ExceptionWithResponseData When a setup error occurs.
	 */
	public function switch_url( int $account_id ): array {
		$state            = $this->state->get();
		$switch_necessary = ! empty( $state['set_id']['data']['old_url'] );
		$set_id_status    = $state['set_id']['status'] ?? MerchantAccountState::STEP_PENDING;
		if ( ! $account_id || MerchantAccountState::STEP_DONE === $set_id_status || ! $switch_necessary ) {
			throw $this->prepare_exception(
				__( 'Attempting invalid URL switch.', 'google-listings-and-ads' )
			);
		}

		$this->allow_switch_url = true;
		$this->use_existing_account_id( $account_id );
		return $this->setup_account( $account_id );
	}

	/**
	 * Create or link an account, overwriting the website claim during the claim step.
	 *
	 * @param int $account_id
	 *
	 * @return array
	 * @throws ExceptionWithResponseData When a setup error occurs.
	 */
	public function overwrite_claim( int $account_id ): array {
		$state               = $this->state->get( false );
		$overwrite_necessary = ! empty( $state['claim']['data']['overwrite_required'] );
		$claim_status        = $state['claim']['status'] ?? MerchantAccountState::STEP_PENDING;
		if ( MerchantAccountState::STEP_DONE === $claim_status || ! $overwrite_necessary ) {
			throw $this->prepare_exception(
				__( 'Attempting invalid claim overwrite.', 'google-listings-and-ads' )
			);
		}

		$this->overwrite_claim = true;
		return $this->setup_account( $account_id );
	}

	/**
	 * Get the connected merchant account.
	 *
	 * @return array
	 */
	public function get_connected_status(): array {
		/** @var NotificationsService $notifications_service */
		$notifications_service = $this->container->get( NotificationsService::class );

		$id                    = $this->options->get_merchant_id();
		$wpcom_rest_api_status = $this->options->get( OptionsInterface::WPCOM_REST_API_STATUS );

		/*
		 * Since we switched to client credentials, authorization is granted by default.
		 * Set status to 'approved' if not set. This code is preserved for future UI functionality.
		 */
		if ( ! $wpcom_rest_api_status ) {
			$wpcom_rest_api_status = OAuthService::STATUS_APPROVED;
			$this->options->update( OptionsInterface::WPCOM_REST_API_STATUS, $wpcom_rest_api_status );
		}

		// If token is revoked outside the extension. Set the status as error to force the merchant to grant access again.
		if ( $wpcom_rest_api_status === 'approved' && ! $this->is_wpcom_api_status_healthy() ) {
			$wpcom_rest_api_status = OAuthService::STATUS_ERROR;
			$this->options->update( OptionsInterface::WPCOM_REST_API_STATUS, $wpcom_rest_api_status );
		}

		$status = [
			'id'                           => $id,
			'status'                       => $id ? 'connected' : 'disconnected',
			'notification_service_enabled' => $notifications_service->is_enabled(),
			'wpcom_rest_api_status'        => $wpcom_rest_api_status,
		];

		$incomplete = $this->state->last_incomplete_step();
		if ( ! empty( $incomplete ) ) {
			$status['status'] = 'incomplete';
			$status['step']   = $incomplete;
		}

		return $status;
	}

	/**
	 * Return the setup status to determine what step to continue at.
	 *
	 * @return array
	 */
	public function get_setup_status(): array {
		return $this->container->get( MerchantCenterService::class )->get_setup_status();
	}

	/**
	 * Disconnect Merchant Center account
	 */
	public function disconnect() {
		$this->options->delete( OptionsInterface::CONTACT_INFO_SETUP );
		$this->options->delete( OptionsInterface::MC_SETUP_COMPLETED_AT );
		$this->options->delete( OptionsInterface::MERCHANT_ACCOUNT_STATE );
		$this->options->delete( OptionsInterface::MERCHANT_CENTER );
		$this->options->delete( OptionsInterface::SITE_VERIFICATION );
		$this->options->delete( OptionsInterface::TARGET_AUDIENCE );
		$this->options->delete( OptionsInterface::MERCHANT_ID );
		$this->options->delete( OptionsInterface::CLAIMED_URL_HASH );

		$this->container->get( MerchantStatuses::class )->delete();

		$this->container->get( MerchantIssueTable::class )->truncate();
		$this->container->get( ShippingRateTable::class )->truncate();
		$this->container->get( ShippingTimeTable::class )->truncate();

		$this->container->get( JobRepository::class )->get( CleanupSyncedProducts::class )->schedule();

		$this->container->get( TransientsInterface::class )->delete( TransientsInterface::MC_ACCOUNT_REVIEW );
		$this->container->get( TransientsInterface::class )->delete( TransientsInterface::URL_MATCHES );
		$this->container->get( TransientsInterface::class )->delete( TransientsInterface::MC_IS_SUBACCOUNT );
	}

	/**
	 * Performs the steps necessary to initialize a Merchant Center account.
	 * Should always resume up at the last pending or unfinished step. If the Merchant Center account
	 * has already been created, the ID is simply returned.
	 *
	 * @return array The newly created (or pre-existing) Merchant account data.
	 * @throws ExceptionWithResponseData If an error occurs during any step.
	 * @throws Exception                 If the step is unknown.
	 * @throws ApiNotReady               If we should wait to complete the next step.
	 */
	private function setup_account_steps() {
		$state       = $this->state->get();
		$merchant_id = $this->options->get_merchant_id();
		$merchant    = $this->container->get( Merchant::class );
		$middleware  = $this->container->get( Middleware::class );

		foreach ( $state as $name => &$step ) {
			if ( MerchantAccountState::STEP_DONE === $step['status'] ) {
				continue;
			}

			if ( 'link' === $name ) {
				$time_to_wait = $this->state->get_seconds_to_wait_after_created();
				if ( $time_to_wait ) {
					sleep( $time_to_wait );
				}
			}

			try {
				switch ( $name ) {
					case 'set_id':
						// Just in case, don't create another merchant ID.
						if ( ! empty( $merchant_id ) ) {
							break;
						}
						$merchant_id                       = $middleware->create_merchant_account();
						$step['data']['from_mca']          = true;
						$step['data']['created_timestamp'] = time();
						break;
					case 'verify':
						// Skip if previously verified.
						if ( $this->state->is_site_verified() ) {
							break;
						}

						$site_url = esc_url_raw( $this->get_site_url() );
						$this->container->get( SiteVerification::class )->verify_site( $site_url );
						break;
					case 'link':
						$middleware->link_merchant_to_mca();
						break;
					case 'claim':
						// At this step, the website URL is assumed to be correct.
						// If the URL is already claimed, no claim should be attempted.
						if ( $merchant->get_accountstatus( $merchant_id )->getWebsiteClaimed() ) {
							break;
						}

						if ( $this->overwrite_claim ) {
							$middleware->claim_merchant_website( true );
						} else {
							$merchant->claimwebsite();
						}
						break;
					case 'sdi_update':
						$middleware->update_sdi_merchant_account();
						break;
					case 'link_ads':
						// Continue to next step if Ads account is not connected yet.
						if ( ! $this->options->get_ads_id() ) {
							// Save step as pending and continue the foreach loop with `continue 2`.
							$state[ $name ]['status'] = MerchantAccountState::STEP_PENDING;
							$this->state->update( $state );
							continue 2;
						}

						$this->link_ads_account();
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
				$this->state->update( $state );
			} catch ( Exception $e ) {
				$step['status']  = MerchantAccountState::STEP_ERROR;
				$step['message'] = $e->getMessage();

				// URL already claimed.
				if ( 'claim' === $name && 403 === $e->getCode() ) {
					$data = [
						'id'          => $merchant_id,
						'website_url' => $this->strip_url_protocol(
							esc_url_raw( $this->get_site_url() )
						),
					];

					// Sub-account: request overwrite confirmation.
					if ( $state['set_id']['data']['from_mca'] ?? true ) {
						do_action( 'woocommerce_gla_site_claim_overwrite_required', [] );
						$step['data']['overwrite_required'] = true;

						$e = $this->prepare_exception( $e->getMessage(), $data, $e->getCode() );
					} else {
						do_action( 'woocommerce_gla_site_claim_failure', [ 'details' => 'independent_account' ] );

						// Independent account: overwrite not possible.
						$e = $this->prepare_exception(
							__( 'Unable to claim website URL with this Merchant Center Account.', 'google-listings-and-ads' ),
							$data,
							406
						);
					}
				} elseif ( 'link' === $name && 401 === $e->getCode() ) {
					// New sub-account not yet manipulable.
					$state['set_id']['data']['created_timestamp'] = time();

					$e = ApiNotReady::retry_after( MerchantAccountState::MC_DELAY_AFTER_CREATE );
				}

				$this->state->update( $state );
				throw $e;
			}
		}

		return [ 'id' => $merchant_id ];
	}

	/**
	 * Restart the account setup when we are connecting with a different account ID.
	 * Do not allow reset when the full setup process has completed.
	 *
	 * @throws ExceptionWithResponseData When the full setup process is completed.
	 */
	private function reset_account_setup() {
		// Can't reset if the MC connection process has been completed previously.
		if ( $this->container->get( MerchantCenterService::class )->is_setup_complete() ) {
			throw $this->prepare_exception(
				sprintf(
					/* translators: 1: is a numeric account ID */
					__( 'Merchant Center account already connected: %d', 'google-listings-and-ads' ),
					$this->options->get_merchant_id()
				)
			);
		}

		$this->disconnect();
	}

	/**
	 * Ensure the Merchant Center account's Website URL matches the site URL. Update an empty value or
	 * a different, unclaimed URL value. Throw a 409 exception if a different, claimed URL is found.
	 *
	 * @param int $merchant_id The Merchant Center account to update.
	 *
	 * @throws ExceptionWithResponseData If the account URL doesn't match the site URL or the URL is invalid.
	 */
	private function maybe_add_merchant_center_url( int $merchant_id ) {
		$site_url = esc_url_raw( $this->get_site_url() );

		if ( ! wc_is_valid_url( $site_url ) ) {
			throw $this->prepare_exception( __( 'Invalid site URL.', 'google-listings-and-ads' ) );
		}

		/** @var Merchant $merchant */
		$merchant = $this->container->get( Merchant::class );

		/** @var Account $account */
		$account     = $merchant->get_account( $merchant_id );
		$account_url = $account->getWebsiteUrl() ?: '';

		if ( untrailingslashit( $site_url ) !== untrailingslashit( $account_url ) ) {

			$is_website_claimed = $merchant->get_accountstatus( $merchant_id )->getWebsiteClaimed();

			if ( ! empty( $account_url ) && $is_website_claimed && ! $this->allow_switch_url ) {
				$state                              = $this->state->get();
				$state['set_id']['data']['old_url'] = $account_url;
				$state['set_id']['status']          = MerchantAccountState::STEP_ERROR;
				$this->state->update( $state );

				$clean_account_url = $this->strip_url_protocol( $account_url );
				$clean_site_url    = $this->strip_url_protocol( $site_url );

				do_action( 'woocommerce_gla_url_switch_required', [] );

				throw $this->prepare_exception(
					sprintf(
					/* translators: 1: is a website URL (without the protocol) */
						__( 'This Merchant Center account already has a verified and claimed URL, %1$s', 'google-listings-and-ads' ),
						$clean_account_url
					),
					[
						'id'          => $merchant_id,
						'claimed_url' => $clean_account_url,
						'new_url'     => $clean_site_url,
					],
					409
				);
			}

			$account->setWebsiteUrl( $site_url );
			$merchant->update_account( $account );

			// Clear previous hashed URL.
			$this->options->delete( OptionsInterface::CLAIMED_URL_HASH );

			do_action( 'woocommerce_gla_url_switch_success', [] );
		}
	}

	/**
	 * Get the callback function for linking an Ads account.
	 *
	 * @throws Exception When the merchant account hasn't been set yet.
	 */
	private function link_ads_account() {
		if ( ! $this->options->get_merchant_id() ) {
			throw new Exception( 'A Merchant Center account must be connected' );
		}

		$ads_state = $this->container->get( AdsAccountState::class );

		// Create link for Merchant and accept it in Ads.
		$waiting_acceptance = $this->container->get( Merchant::class )->link_ads_id( $this->options->get_ads_id() );

		if ( $waiting_acceptance ) {
			$this->container->get( Ads::class )->accept_merchant_link( $this->options->get_merchant_id() );
		}

		$ads_state->complete_step( 'link_merchant' );
	}

	/**
	 * Prepares an Exception to be thrown with Merchant data:
	 * - Ensure it has the merchant_id value
	 * - Default to a 400 error code
	 *
	 * @param string   $message
	 * @param array    $data
	 * @param int|null $code
	 *
	 * @return ExceptionWithResponseData
	 */
	private function prepare_exception( string $message, array $data = [], ?int $code = null ): ExceptionWithResponseData {
		$merchant_id = $this->options->get_merchant_id();

		if ( $merchant_id && ! isset( $data['id'] ) ) {
			$data['id'] = $merchant_id;
		}

		return new ExceptionWithResponseData( $message, $code ?: 400, null, $data );
	}

	/**
	 * Delete the option regarding WPCOM authorization
	 *
	 * @return bool
	 */
	public function reset_wpcom_api_authorization_data(): bool {
		$this->delete_wpcom_api_auth_nonce();
		$this->delete_wpcom_api_status_transient();
		return $this->options->delete( OptionsInterface::WPCOM_REST_API_STATUS );
	}

	/**
	 * Update the status of the merchant granting access to Google's WPCOM app in the database.
	 * Before updating the status in the DB it will compare the nonce stored in the DB with the nonce passed to the API.
	 *
	 * @param string $status The status of the merchant granting access to Google's WPCOM app.
	 * @param string $nonce  The nonce provided by Google in the URL query parameter when Google redirects back to merchant's site.
	 *
	 * @return bool
	 * @throws ExceptionWithResponseData If the stored nonce / nonce from query param is not provided, or the nonces mismatch.
	 */
	public function update_wpcom_api_authorization( string $status, string $nonce ): bool {
		try {
			$stored_nonce = $this->options->get( OptionsInterface::GOOGLE_WPCOM_AUTH_NONCE );
			if ( empty( $stored_nonce ) ) {
				throw $this->prepare_exception(
					__( 'No stored nonce found in the database, skip updating auth status.', 'google-listings-and-ads' )
				);
			}

			if ( empty( $nonce ) ) {
				throw $this->prepare_exception(
					__( 'Nonce is not provided, skip updating auth status.', 'google-listings-and-ads' )
				);
			}

			if ( $stored_nonce !== $nonce ) {
				$this->delete_wpcom_api_auth_nonce();
				throw $this->prepare_exception(
					__( 'Nonces mismatch, skip updating auth status.', 'google-listings-and-ads' )
				);
			}

			$this->delete_wpcom_api_auth_nonce();

			/**
			* When the WPCOM Authorization status has been updated.
			*
			* @event update_wpcom_api_authorization
			* @property string status The status of the request.
			* @property int|null blog_id The blog ID.
			*/
			do_action(
				'woocommerce_gla_track_event',
				'update_wpcom_api_authorization',
				[
					'status'  => $status,
					'blog_id' => Jetpack_Options::get_option( 'id' ),
				]
			);

			$this->delete_wpcom_api_status_transient();
			return $this->options->update( OptionsInterface::WPCOM_REST_API_STATUS, $status );
		} catch ( ExceptionWithResponseData $e ) {

			/**
			* When the WPCOM Authorization status has been updated with errors.
			*
			* @event update_wpcom_api_authorization
			* @property string status The status of the request.
			* @property int|null blog_id The blog ID.
			*/
			do_action(
				'woocommerce_gla_track_event',
				'update_wpcom_api_authorization',
				[
					'status'  => $e->getMessage(),
					'blog_id' => Jetpack_Options::get_option( 'id' ),
				]
			);

			throw $e;
		}
	}

	/**
	 * Delete the nonce of "verifying Google is the one redirect back to merchant site and set the auth status" in the database.
	 *
	 * @return bool
	 */
	public function delete_wpcom_api_auth_nonce(): bool {
		return $this->options->delete( OptionsInterface::GOOGLE_WPCOM_AUTH_NONCE );
	}

	/**
	 * Deletes the transient storing the WPCOM Status data.
	 */
	public function delete_wpcom_api_status_transient(): void {
		$transients = $this->container->get( TransientsInterface::class );
		$transients->delete( TransientsInterface::WPCOM_API_STATUS );
	}

	/**
	 * Check if the WPCOM API Status is healthy by doing a request to /wc/partners/google/remote-site-status endpoint in WPCOM.
	 *
	 * @return bool True when the status is healthy, false otherwise.
	 */
	public function is_wpcom_api_status_healthy() {
		/** @var TransientsInterface $transients */
		$transients = $this->container->get( TransientsInterface::class );
		$status     = $transients->get( TransientsInterface::WPCOM_API_STATUS );

		if ( ! $status ) {

			$integration_status_args = [
				'method'  => 'GET',
				'timeout' => 30,
				'url'     => 'https://public-api.wordpress.com/wpcom/v2/sites/' . Jetpack_Options::get_option( 'id' ) . '/wc/partners/google/remote-site-status',
				'user_id' => get_current_user_id(),
			];

			$integration_remote_request_response = Client::remote_request( $integration_status_args, null );

			if ( is_wp_error( $integration_remote_request_response ) ) {
				$status = [ 'is_healthy' => false ];
			} else {
				$status = json_decode( wp_remote_retrieve_body( $integration_remote_request_response ), true ) ?? [ 'is_healthy' => false ];

				/*
				 * Since we switched from OAuth to client credentials,
				 * WPCOM's partner token validation returns false. Inject true status until
				 * the issue is resolved. Preserving for future UI functionality.
				 */
				if ( isset( $status['is_partner_token_healthy'] ) && ! $status['is_partner_token_healthy'] ) {
					$status['is_partner_token_healthy'] = true;
				}
			}

			$transients->set( TransientsInterface::WPCOM_API_STATUS, $status, MINUTE_IN_SECONDS * 30 );
		}

		return isset( $status['is_healthy'] ) && $status['is_healthy'] && $status['is_wc_rest_api_healthy'] && $status['is_partner_token_healthy'];
	}
}
