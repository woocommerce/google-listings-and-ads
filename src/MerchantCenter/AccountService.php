<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ApiNotReady;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupSyncedProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Exception;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountService
 *
 * Container used to access:
 * - CleanupSyncedProducts
 * - Merchant
 * - MerchantAccountState
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
class AccountService implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

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
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->state     = $container->get( MerchantAccountState::class );
		$this->container = $container;
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
		$id     = $this->options->get_merchant_id();
		$status = [
			'id'     => $id,
			'status' => $id ? 'connected' : 'disconnected',
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

		$this->container->get( CleanupSyncedProducts::class )->schedule();

		$this->container->get( TransientsInterface::class )->delete( TransientsInterface::MC_ACCOUNT_REVIEW );
		$this->container->get( TransientsInterface::class )->delete( TransientsInterface::URL_MATCHES );
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
		$account_url = $account->getWebsiteUrl();

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
	private function prepare_exception( string $message, array $data = [], int $code = null ): ExceptionWithResponseData {
		$merchant_id = $this->options->get_merchant_id();

		if ( $merchant_id && ! isset( $data['id'] ) ) {
			$data['id'] = $merchant_id;
		}

		return new ExceptionWithResponseData( $message, $code ?: 400, null, $data );
	}
}
