<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\MerchantApiException;
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

}
