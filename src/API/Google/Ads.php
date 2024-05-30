<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAccountAccessQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAccountQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsBillingStatusQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsProductLinkInvitationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Exception;
use Google\Ads\GoogleAds\Util\V16\ResourceNames;
use Google\Ads\GoogleAds\V16\Enums\AccessRoleEnum\AccessRole;
use Google\Ads\GoogleAds\V16\Enums\ProductLinkInvitationStatusEnum\ProductLinkInvitationStatus;
use Google\Ads\GoogleAds\V16\Resources\ProductLinkInvitation;
use Google\Ads\GoogleAds\V16\Services\ListAccessibleCustomersRequest;
use Google\Ads\GoogleAds\V16\Services\UpdateProductLinkInvitationRequest;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ads
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Ads implements OptionsAwareInterface {

	use ExceptionTrait;
	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * Ads constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Get Ads accounts associated with the connected Google account.
	 *
	 * @return array
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_ads_accounts(): array {
		try {
			$customers = $this->client->getCustomerServiceClient()->listAccessibleCustomers( new ListAccessibleCustomersRequest() );
			$accounts  = [];

			foreach ( $customers->getResourceNames() as $name ) {
				$account = $this->get_account_details( $name );

				if ( $account ) {
					$accounts[] = $account;
				}
			}

			return $accounts;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			// Return an empty list if the user has not signed up to ads yet.
			if ( isset( $errors['NOT_ADS_USER'] ) ) {
				return [];
			}

			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving accounts: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Get billing status.
	 *
	 * @return string
	 */
	public function get_billing_status(): string {
		$ads_id = $this->options->get_ads_id();

		if ( ! $ads_id ) {
			return BillingSetupStatus::UNKNOWN;
		}

		try {
			$results = ( new AdsBillingStatusQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->get_results();

			foreach ( $results->iterateAllElements() as $row ) {
				$billing_setup = $row->getBillingSetup();
				$status        = BillingSetupStatus::label( $billing_setup->getStatus() );
				return apply_filters( 'woocommerce_gla_ads_billing_setup_status', $status, $ads_id );
			}
		} catch ( ApiException | ValidationException $e ) {
			// Do not act upon error as we might not have permission to access this account yet.
			if ( 'PERMISSION_DENIED' !== $e->getStatus() ) {
				do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
			}
		}

		return apply_filters( 'woocommerce_gla_ads_billing_setup_status', BillingSetupStatus::UNKNOWN, $ads_id );
	}

	/**
	 * Accept a link from a merchant account.
	 *
	 * @param int $merchant_id Merchant Center account id.
	 * @throws Exception When a link is unavailable.
	 */
	public function accept_merchant_link( int $merchant_id ) {
		$link        = $this->get_merchant_link( $merchant_id );
		$link_status = $link->getStatus();
		if ( $link_status === ProductLinkInvitationStatus::ACCEPTED ) {
			return;
		}
		$request = new UpdateProductLinkInvitationRequest();
		$request->setCustomerId( $this->options->get_ads_id() );
		$request->setResourceName( $link->getResourceName() );
		$request->setProductLinkInvitationStatus( ProductLinkInvitationStatus::ACCEPTED );
		$this->client->getProductLinkInvitationServiceClient()->updateProductLinkInvitation( $request );
	}

	/**
	 * Check if we have access to the ads account.
	 *
	 * @param string $email Email address of the connected account.
	 *
	 * @return bool
	 */
	public function has_access( string $email ): bool {
		$ads_id = $this->options->get_ads_id();

		try {
			$results = ( new AdsAccountAccessQuery() )
				->set_client( $this->client, $ads_id )
				->where( 'customer_user_access.email_address', $email )
				->get_results();

			foreach ( $results->iterateAllElements() as $row ) {
				$access = $row->getCustomerUserAccess();
				if ( AccessRole::ADMIN === $access->getAccessRole() ) {
					return true;
				}
			}
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return false;
	}

	/**
	 * Get the ads account currency.
	 *
	 * @since 1.4.1
	 *
	 * @return string
	 */
	public function get_ads_currency(): string {
		// Retrieve account currency from the API if we haven't done so previously.
		if ( $this->options->get_ads_id() && ! $this->options->get( OptionsInterface::ADS_ACCOUNT_CURRENCY ) ) {
			$this->request_ads_currency();
		}

		return strtoupper( $this->options->get( OptionsInterface::ADS_ACCOUNT_CURRENCY ) ?? get_woocommerce_currency() );
	}

	/**
	 * Request the Ads Account currency, and cache it as an option.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean
	 */
	public function request_ads_currency(): bool {
		try {
			$ads_id   = $this->options->get_ads_id();
			$account  = ResourceNames::forCustomer( $ads_id );
			$customer = ( new AdsAccountQuery() )
				->set_client( $this->client, $ads_id )
				->columns( [ 'customer.currency_code' ] )
				->where( 'customer.resource_name', $account, '=' )
				->get_result()
				->getCustomer();

			$currency = $customer->getCurrencyCode();
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
			$currency = null;
		}

		return $this->options->update( OptionsInterface::ADS_ACCOUNT_CURRENCY, $currency );
	}

	/**
	 * Save the Ads account currency to the same value as the Store currency.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean
	 */
	public function use_store_currency(): bool {
		return $this->options->update( OptionsInterface::ADS_ACCOUNT_CURRENCY, get_woocommerce_currency() );
	}

	/**
	 * Convert ads ID from a resource name to an int.
	 *
	 * @param string $name Resource name containing ID number.
	 *
	 * @return int
	 */
	public function parse_ads_id( string $name ): int {
		return absint( str_replace( 'customers/', '', $name ) );
	}

	/**
	 * Update the Ads ID to use for requests.
	 *
	 * @param int $id Ads ID number.
	 *
	 * @return bool
	 */
	public function update_ads_id( int $id ): bool {
		return $this->options->update( OptionsInterface::ADS_ID, $id );
	}

	/**
	 * Returns true if the Ads id exists in the options.
	 *
	 * @return bool
	 */
	public function ads_id_exists(): bool {
		return ! empty( $this->options->get( OptionsInterface::ADS_ID ) );
	}

	/**
	 * Update the billing flow URL so we can retrieve it again later.
	 *
	 * @param string $url Billing flow URL.
	 *
	 * @return bool
	 */
	public function update_billing_url( string $url ): bool {
		return $this->options->update( OptionsInterface::ADS_BILLING_URL, $url );
	}

	/**
	 * Fetch the account details.
	 * Returns null for any account that fails or is not the right type.
	 *
	 * @param string $account Customer resource name.
	 * @return null|array
	 */
	private function get_account_details( string $account ): ?array {
		try {
			$customer = ( new AdsAccountQuery() )
				->set_client( $this->client, $this->parse_ads_id( $account ) )
				->where( 'customer.resource_name', $account, '=' )
				->get_result()
				->getCustomer();

			if ( ! $customer || $customer->getManager() || $customer->getTestAccount() ) {
				return null;
			}

			return [
				'id'   => $customer->getId(),
				'name' => $customer->getDescriptiveName(),
			];
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return null;
	}

	/**
	 * Get the link from a merchant account.
	 *
	 * @param int $merchant_id Merchant Center account id.
	 *
	 * @return ProductLinkInvitation
	 * @throws Exception When the merchant link hasn't been created.
	 */
	private function get_merchant_link( int $merchant_id ): ProductLinkInvitation {
		$res = ( new AdsProductLinkInvitationQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'product_link_invitation.status', [ ProductLinkInvitationStatus::name( ProductLinkInvitationStatus::ACCEPTED ), ProductLinkInvitationStatus::name( ProductLinkInvitationStatus::PENDING_APPROVAL ) ], 'IN' )
			->get_results();

		foreach ( $res->iterateAllElements() as $row ) {
			$link  = $row->getProductLinkInvitation();
			$mc    = $link->getMerchantCenter();
			$mc_id = $mc->getMerchantCenterId();
			if ( absint( $mc_id ) === $merchant_id ) {
				return $link;
			}
		}

		throw new Exception( __( 'Merchant link is not available to accept', 'google-listings-and-ads' ) );
	}

	/**
	 * Check if the user has accepted the customer data terms for enhanced conversion tracking.
	 * Returns false for any account that fails.
	 *
	 * @return boolean
	 */
	public function get_accepted_customer_data_terms(): bool {
		$ads_id = $this->options->get_ads_id();

		// Return if no ads id present.
		if ( ! $ads_id ) {
			return false;
		}

		try {
			$accepted_terms = $this->options->get( OptionsInterface::ADS_CUSTOMER_DATA_TERMS, null );

			// Retrieve the terms acceptance data from options.
			if ( null !== $accepted_terms ) {
				return (bool) apply_filters( 'woocommerce_gla_ads_enhanced_conversion_customer_data_terms', (bool) $accepted_terms );
			}

			$customer = ( new AdsAccountQuery() )
				->set_client( $this->client, $ads_id )
				->columns( [ 'customer.conversion_tracking_setting.accepted_customer_data_terms' ] )
				->get_result()
				->getCustomer();

			if ( ! $customer ) {
				return false;
			}

			$conversion_tracking_setting = $customer->getConversionTrackingSetting();

			$accepted = $conversion_tracking_setting->getAcceptedCustomerDataTerms();

			// Save the data terms in options as those cannot be reverted.
			$this->options->update( OptionsInterface::ADS_CUSTOMER_DATA_TERMS, $accepted );

			return (bool) apply_filters( 'woocommerce_gla_ads_enhanced_conversion_customer_data_terms', (bool) $accepted );
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return false;
	}

	/**
	 * Updates the enhanced ads conversion status.
	 *
	 * @param string $status The status value
	 *
	 * @return string
	 */
	public function update_enhanced_conversion_status( string $status ): string {
		$this->options->update( OptionsInterface::ADS_ENHANCED_CONVERSION_STATUS, $status );

		return $status;
	}

	/**
	 * Retrieve the enhanced ads conversion status. Possible values are: enabled, disabled and pending
	 *
	 * @return string|null
	 */
	public function get_enhanced_conversion_status(): ?string {
		$result = $this->options->get( OptionsInterface::ADS_ENHANCED_CONVERSION_STATUS, null );

		if ( ! is_scalar( $result ) ) {
			return null;
		}

		return strval( $result );
	}
}
