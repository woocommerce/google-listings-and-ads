<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Google\Service\ShoppingContent\AccountBusinessInformation;

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
	 * @throws MerchantApiException If the Merchant Center account can't be retrieved.
	 */
	public function get_contact_information(): ?AccountBusinessInformation {
		$business_information = $this->merchant->get_account()->getBusinessInformation();

		return $business_information ?: null;
	}

	/**
	 * Update the phone number for the connected Merchant Center account.
	 *
	 * @param string|null $phone_number The new phone number to add the the Merchant Center account.
	 *
	 * @return AccountBusinessInformation The contact information associated with the Merchant Center account.
	 *
	 * @throws MerchantApiException If the Merchant Center account can't be retrieved or updated.
	 * @throws InvalidValue If the provided phone number is invalid.
	 */
	public function update_phone_number( ?string $phone_number ): AccountBusinessInformation {
		$business_information = $this->get_contact_information() ?: new AccountBusinessInformation();

		if ( ! empty( $phone_number ) ) {
			if ( ! $this->validate_phone_number( $phone_number ) ) {
				throw new InvalidValue( __( 'Invalid phone number.', 'google-listings-and-ads' ) );
			}
			$phone_number = $this->sanitize_phone_number( $phone_number );
		} else {
			$phone_number = null;
		}

		$business_information->setPhoneNumber( $phone_number );

		$this->update_contact_information( $business_information );

		return $business_information;
	}

	/**
	 * Update the address for the connected Merchant Center account to the store address set in WooCommerce
	 * settings.
	 *
	 * @return AccountBusinessInformation The contact information associated with the Merchant Center account.
	 *
	 * @throws MerchantApiException If the Merchant Center account can't be retrieved or updated.
	 */
	public function update_address_based_on_store_settings(): AccountBusinessInformation {
		$business_information = $this->get_contact_information() ?: new AccountBusinessInformation();

		$store_address = $this->settings->get_store_address();
		$business_information->setAddress( $store_address );

		$this->update_contact_information( $business_information );

		return $business_information;
	}

	/**
	 * Update the contact information for the connected Merchant Center account.
	 *
	 * @param AccountBusinessInformation $business_information
	 *
	 * @throws MerchantApiException If the Merchant Center account can't be retrieved or updated.
	 */
	protected function update_contact_information( AccountBusinessInformation $business_information ): void {
		$account = $this->merchant->get_account();
		$account->setBusinessInformation( $business_information );
		$this->merchant->update_account( $account );
	}

	/**
	 * Validate that the phone number doesn't contain invalid characters.
	 * Allowed: ()-.0123456789 and space
	 *
	 * @param string|int $phone_number The phone number to validate.
	 *
	 * @return bool
	 */
	public function validate_phone_number( $phone_number ): bool {
		// Disallowed characters.
		if ( is_string( $phone_number ) && preg_match( '/[^0-9() \-.+]/', $phone_number ) ) {
			return false;
		}

		// Don't allow integer 0
		return ! empty( $phone_number );
	}

	/**
	 *
	 * Sanitize the phone number, leaving only `+` (plus) and numbers.
	 *
	 * @param string|int $phone_number The phone number to sanitize.
	 *
	 * @return string
	 */
	public function sanitize_phone_number( $phone_number ): string {
		return preg_replace( '/[^+0-9]/', '', "$phone_number" );
	}
}
