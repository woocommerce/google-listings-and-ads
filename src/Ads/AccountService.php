<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsConversionAction;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BillingSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Exception;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountService
 *
 * Container used to access:
 * - Ads
 * - AdsAccountState
 * - AdsConversionAction
 * - Merchant
 * - Middleware
 *
 * @since 1.11.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AccountService implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var AdsAccountState
	 */
	protected $state;

	/**
	 * AccountService constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->state     = $container->get( AdsAccountState::class );
		$this->container = $container;
	}

	/**
	 * Get Ads IDs associated with the connected Google account.
	 *
	 * @return int[]
	 * @throws Exception When an API error occurs.
	 */
	public function get_account_ids(): array {
		return $this->container->get( Middleware::class )->get_ads_account_ids();
	}

	/**
	 * Get the connected ads account.
	 *
	 * @return array
	 */
	public function get_connected_account(): array {
		return $this->container->get( Middleware::class )->get_connected_ads_account();
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

					case 'link_merchant':
						$this->link_merchant_account();
						break;

					case 'conversion_action':
						$this->create_conversion_action();
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

		return [
			'status'      => $status,
			'billing_url' => $this->options->get( OptionsInterface::ADS_BILLING_URL ),
		];
	}

	/**
	 * Disconnect Ads account
	 */
	public function disconnect() {
		$this->options->delete( OptionsInterface::ADS_ACCOUNT_CURRENCY );
		$this->options->delete( OptionsInterface::ADS_ACCOUNT_STATE );
		$this->options->delete( OptionsInterface::ADS_BILLING_URL );
		$this->options->delete( OptionsInterface::ADS_CONVERSION_ACTION );
		$this->options->delete( OptionsInterface::ADS_ID );
		$this->options->delete( OptionsInterface::ADS_SETUP_COMPLETED_AT );
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
	 * @throws Exception When the merchant or ads account hasn't been set yet.
	 */
	private function link_merchant_account() {
		if ( ! $this->options->get_merchant_id() ) {
			throw new Exception( 'A Merchant Center account must be connected' );
		}

		if ( ! $this->options->get_ads_id() ) {
			throw new Exception( 'An Ads account must be connected' );
		}

		// Create link for Merchant and accept it in Ads.
		$this->container->get( Merchant::class )->link_ads_id( $this->options->get_ads_id() );
		$this->container->get( Ads::class )->accept_merchant_link( $this->options->get_merchant_id() );
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
