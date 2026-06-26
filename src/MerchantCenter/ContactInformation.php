<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountAddress;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountBusinessInformation;

/**
 * Class ContactInformation.
 *
 * @since 1.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class ContactInformation implements Service {

	/**
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * ContactInformation constructor.
	 *
	 * @param Merchant $merchant
	 * @param Settings $settings
	 */
	public function __construct( Merchant $merchant, Settings $settings ) {
		$this->merchant = $merchant;
		$this->settings = $settings;
	}

	/**
	 * Get the contact information for the connected Merchant Center account.
	 *
	 * @return AccountBusinessInformation|null The contact information associated with the Merchant Center account or
	 *                                         null.
	 *
	 * @throws ExceptionWithResponseData If the Merchant Center account can't be retrieved.
	 */
	public function get_contact_information(): ?AccountBusinessInformation {
		try {
			$business_info = $this->merchant->get_business_info();
		} catch ( MerchantApiException $e ) {
			throw new ExceptionWithResponseData( $e->getMessage(), $e->getCode(), $e );
		}

		if ( empty( $business_info ) ) {
			return null;
		}

		return $this->map_business_info( $business_info );
	}

	/**
	 * Map a Merchant API businessInfo resource onto an AccountBusinessInformation.
	 *
	 * @param array $business_info BusinessInfo resource decoded as an array.
	 *
	 * @return AccountBusinessInformation
	 */
	protected function map_business_info( array $business_info ): AccountBusinessInformation {
		$contact_information = new AccountBusinessInformation();

		if ( ! empty( $business_info['address'] ) ) {
			$contact_information->setAddress( $this->map_address( $business_info['address'] ) );
		}

		if ( ! empty( $business_info['phone']['e164Number'] ) ) {
			$contact_information->setPhoneNumber( $business_info['phone']['e164Number'] );
		}

		if ( ! empty( $business_info['phoneVerificationState'] ) ) {
			// The frontend only understands verified/unverified; fold UNSPECIFIED and
			// any other non-verified state into UNVERIFIED.
			$contact_information->setPhoneVerificationStatus(
				'PHONE_VERIFICATION_STATE_VERIFIED' === $business_info['phoneVerificationState'] ? 'VERIFIED' : 'UNVERIFIED'
			);
		}

		return $contact_information;
	}

	/**
	 * Map a Merchant API PostalAddress onto an AccountAddress.
	 *
	 * @param array $address PostalAddress decoded as an array.
	 *
	 * @return AccountAddress
	 */
	protected function map_address( array $address ): AccountAddress {
		$region  = $address['administrativeArea'] ?? '';
		$country = $address['regionCode'] ?? '';

		// Normalize the region to the state name so it matches the store address,
		// which is built with state names. maybe_get_state_name leaves a name unchanged.
		if ( '' !== $region && '' !== $country ) {
			$region = $this->settings->maybe_get_state_name( $region, $country );
		}

		$account_address = new AccountAddress();
		$account_address->setCountry( $country );
		$account_address->setPostalCode( $address['postalCode'] ?? '' );
		$account_address->setLocality( $address['locality'] ?? '' );
		$account_address->setRegion( $region );
		$account_address->setStreetAddress( implode( "\n", $address['addressLines'] ?? [] ) );

		return $account_address;
	}

	/**
	 * Update the address for the connected Merchant Center account to the store address set in WooCommerce
	 * settings.
	 *
	 * @return AccountBusinessInformation The contact information associated with the Merchant Center account.
	 *
	 * @throws ExceptionWithResponseData If the Merchant Center account can't be retrieved or updated.
	 */
	public function update_address_based_on_store_settings(): AccountBusinessInformation {
		// Read the current business information from the account so the address update
		// preserves the other fields (phone, customer service) instead of the reduced
		// view returned by get_contact_information().
		$account              = $this->merchant->get_account();
		$business_information = $account->getBusinessInformation() ?: new AccountBusinessInformation();
		$business_information->setAddress( $this->settings->get_store_address() );

		$account->setBusinessInformation( $business_information );
		$this->merchant->update_account( $account );

		return $business_information;
	}
}
