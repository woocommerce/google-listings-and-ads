<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsConversionAction;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BillingSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountService
 *
 * Container used to access:
 * - Ads
 * - AdsConversionAction
 * - Connection
 * - Merchant
 * - MerchantAccountState
 * - Middleware
 * - TransientsInterface
 *
 * @since 1.11.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AccountService implements ContainerAwareInterface, OptionsAwareInterface, Service {

	use ContainerAwareTrait;
	use OptionsAwareTrait;

	/**
	 * @var AdsAccountState
	 */
	protected $state;

	/**
	 * AccountService constructor.
	 *
	 * @param AdsAccountState $state
	 */
	public function __construct( AdsAccountState $state ) {
		$this->state = $state;
	}

	/**
	 * Get Ads accounts associated with the connected Google account.
	 *
	 * @return array
	 * @throws Exception When an API error occurs.
	 */
	public function get_accounts(): array {
		return $this->container->get( Ads::class )->get_ads_accounts();
	}

	/**
	 * Get the connected ads account.
	 *
	 * @return array
	 */
	public function get_connected_account(): array {
		$id = $this->options->get_ads_id();

		$status = [
			'id'       => $id,
			'ocid'     => $this->options->get( OptionsInterface::ADS_ACCOUNT_OCID ),
			'currency' => $this->options->get( OptionsInterface::ADS_ACCOUNT_CURRENCY ),
			'symbol'   => html_entity_decode( get_woocommerce_currency_symbol( $this->options->get( OptionsInterface::ADS_ACCOUNT_CURRENCY ) ), ENT_QUOTES ),
			'status'   => $id ? 'connected' : 'disconnected',
		];

		$incomplete = $this->state->last_incomplete_step();
		if ( ! empty( $incomplete ) ) {
			$status['status'] = 'incomplete';
			$status['step']   = $incomplete;
		}

		$status += $this->state->get_step_data( 'set_id' );

		return $status;
	}

	/**
	 * Use an existing Ads account. Mark the 'set_id' step as done and sets the Ads ID.
	 *
	 * @param int $account_id The Ads account ID to use.
	 *
	 * @throws Exception If there is already an Ads account ID.
	 */
	public function use_existing_account( int $account_id ) {
		$ads_id = $this->options->get_ads_id();
		if ( $ads_id && $ads_id !== $account_id ) {
			throw new Exception(
				/* translators: 1: is a numeric account ID */
				sprintf( __( 'Ads account %1$d already connected.', 'google-listings-and-ads' ), $ads_id )
			);
		}

		$state = $this->state->get();

		// Don't do anything if this step was already finished.
		if ( AdsAccountState::STEP_DONE === $state['set_id']['status'] ) {
			return;
		}

		$this->container->get( Middleware::class )->link_ads_account( $account_id );

		// Skip billing setup flow when using an existing account.
		$state['set_id']['status']  = AdsAccountState::STEP_DONE;
		$state['billing']['status'] = AdsAccountState::STEP_DONE;
		$this->state->update( $state );
	}

	/**
	 * Performs the steps necessary to setup an ads account.
	 * Should always resume up at the last pending or unfinished step.
	 * If the Ads account has already been created, the ID is simply returned.
	 *
	 * @return array The newly created (or pre-existing) Ads ID.
	 * @throws Exception If an error occurs during any step.
	 */
	public function setup_account(): array {
		$state   = $this->state->get();
		$ads_id  = $this->options->get_ads_id();
		$account = [ 'id' => $ads_id ];

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
						$account = $this->container->get( Middleware::class )->create_ads_account();

						$step['data']['sub_account']       = true;
						$step['data']['created_timestamp'] = time();
						break;

					case 'billing':
						$this->check_billing_status( $account );
						break;

					case 'conversion_action':
						$this->create_conversion_action();
						break;

					case 'link_merchant':
						// Continue to next step if the MC account is not connected yet.
						if ( ! $this->options->get_merchant_id() ) {
							// Save step as pending and continue the foreach loop with `continue 2`.
							$state[ $name ]['status'] = AdsAccountState::STEP_PENDING;
							$this->state->update( $state );
							continue 2;
						}

						$this->link_merchant_account();
						break;

					case 'account_access':
						$this->check_ads_account_has_access();
						break;

					default:
						throw new Exception(
							/* translators: 1: is a string representing an unknown step name */
							sprintf( __( 'Unknown ads account creation step %1$s', 'google-listings-and-ads' ), $name )
						);
				}
				$step['status']  = AdsAccountState::STEP_DONE;
				$step['message'] = '';
				$this->state->update( $state );
			} catch ( Exception $e ) {
				$step['status']  = AdsAccountState::STEP_ERROR;
				$step['message'] = $e->getMessage();
				$this->state->update( $state );
				throw $e;
			}
		}

		return $account;
	}

	/**
	 * Gets the billing setup status and returns a setup URL if available.
	 *
	 * @return array
	 */
	public function get_billing_status(): array {
		$status = $this->container->get( Ads::class )->get_billing_status();

		if ( BillingSetupStatus::APPROVED === $status ) {
			$this->state->complete_step( 'billing' );
			return [ 'status' => $status ];
		}

		$billing_url = $this->options->get( OptionsInterface::ADS_BILLING_URL );

		// Check if user has provided the access and ocid is present.
		$connection_status = $this->container->get( Connection::class )->get_status();
		$email             = $connection_status['email'] ?? '';
		$has_access        = $this->container->get( Ads::class )->has_access( $email );
		$ocid              = $this->options->get( OptionsInterface::ADS_ACCOUNT_OCID, null );

		// Link directly to the payment page if the customer already has access.
		if ( $has_access ) {
			$billing_url = add_query_arg(
				[
					'ocid' => $ocid ?: 0,
				],
				'https://ads.google.com/aw/signup/payment'
			);
		}

		return [
			'status'      => $status,
			'billing_url' => $billing_url,
		];
	}

	/**
	 * Check if the Ads account has access.
	 *
	 * @throws ExceptionWithResponseData If the account doesn't have access.
	 */
	private function check_ads_account_has_access() {
		$access_status = $this->get_ads_account_has_access();

		if ( ! $access_status['has_access'] ) {
			throw new ExceptionWithResponseData(
				__( 'Account must be accepted before completing setup.', 'google-listings-and-ads' ),
				428,
				null,
				$access_status
			);
		}
	}

	/**
	 * Gets the Ads account access status.
	 *
	 * @return array {
	 *     Returns the access status, last completed account setup step,
	 *     and invite link if available.
	 *
	 *     @type bool   $has_access  Whether the customer has access to the account.
	 *     @type string $step        The last completed setup step for the Ads account.
	 *     @type string $invite_link The URL to the invite link.
	 * }
	 */
	public function get_ads_account_has_access() {
		$has_access = false;

		// Check if an Ads ID is present.
		if ( $this->options->get_ads_id() ) {
			$connection_status = $this->container->get( Connection::class )->get_status();
			$email             = $connection_status['email'] ?? '';
		}

		// If no email, means google account is not connected.
		if ( ! empty( $email ) ) {
			$has_access = $this->container->get( Ads::class )->has_access( $email );
		}

		// If we have access, complete the step so that it won't be called next time.
		if ( $has_access ) {
			$this->state->complete_step( 'account_access' );
		}

		return [
			'has_access'  => $has_access,
			'step'        => $this->state->last_incomplete_step(),
			'invite_link' => $this->options->get( OptionsInterface::ADS_BILLING_URL, '' ),
		];
	}

	/**
	 * Disconnect Ads account
	 */
	public function disconnect() {
		$this->options->delete( OptionsInterface::ADS_ACCOUNT_CURRENCY );
		$this->options->delete( OptionsInterface::ADS_ACCOUNT_OCID );
		$this->options->delete( OptionsInterface::ADS_ACCOUNT_STATE );
		$this->options->delete( OptionsInterface::ADS_BILLING_URL );
		$this->options->delete( OptionsInterface::ADS_CONVERSION_ACTION );
		$this->options->delete( OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED );
		$this->options->delete( OptionsInterface::ADS_ID );
		$this->options->delete( OptionsInterface::ADS_SETUP_COMPLETED_AT );
		$this->options->delete( OptionsInterface::CAMPAIGN_CONVERT_STATUS );
		$this->container->get( TransientsInterface::class )->delete( TransientsInterface::ADS_CAMPAIGN_COUNT );
	}

	/**
	 * Confirm the billing flow has been completed.
	 *
	 * @param array $account Account details.
	 *
	 * @throws ExceptionWithResponseData If this step hasn't been completed yet.
	 */
	private function check_billing_status( array $account ) {
		$status = BillingSetupStatus::UNKNOWN;

		// Only check billing status if we haven't just created the account.
		if ( empty( $account['billing_url'] ) ) {
			$status = $this->container->get( Ads::class )->get_billing_status();
		}

		if ( BillingSetupStatus::APPROVED !== $status ) {
			throw new ExceptionWithResponseData(
				__( 'Billing setup must be completed.', 'google-listings-and-ads' ),
				428,
				null,
				[
					'billing_url'    => $this->options->get( OptionsInterface::ADS_BILLING_URL ),
					'billing_status' => $status,
				]
			);
		}
	}

	/**
	 * Get the callback function for linking a merchant account.
	 *
	 * @throws Exception When the ads account hasn't been set yet.
	 */
	private function link_merchant_account() {
		if ( ! $this->options->get_ads_id() ) {
			throw new Exception( 'An Ads account must be connected' );
		}

		$mc_state = $this->container->get( MerchantAccountState::class );

		// Create link for Merchant and accept it in Ads.
		$waiting_acceptance = $this->container->get( Merchant::class )->link_ads_id( $this->options->get_ads_id() );

		if ( $waiting_acceptance ) {
			$this->container->get( Ads::class )->accept_merchant_link( $this->options->get_merchant_id() );
		}

		$mc_state->complete_step( 'link_ads' );
	}

	/**
	 * Create the generic GLA conversion action and store the details as an option.
	 *
	 * @throws Exception If the conversion action can't be created.
	 */
	private function create_conversion_action(): void {
		$action = $this->container->get( AdsConversionAction::class )->create_conversion_action();
		$this->options->update( OptionsInterface::ADS_CONVERSION_ACTION, $action );
	}
}
