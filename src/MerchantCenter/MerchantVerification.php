<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ContentApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Google\Service\ShoppingContent\Account;
use Google\Service\ShoppingContent\AccountBusinessInformation;

/**
 * Class MerchantVerification.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MerchantVerification implements Service {

	/**
	 * @var Merchant
	 */
	protected $merchant;

	/**
	 * MerchantVerification constructor.
	 *
	 * @param Merchant $merchant
	 */
	public function __construct( Merchant $merchant ) {
		$this->merchant = $merchant;
	}

	/**
	 * Get the phone number for the connected Merchant Center account.
	 *
	 * @return string|null The phone number associated with the Merchant Center account or null.
	 * @throws ContentApiException If the Merchant Center account can't be retrieved.
	 */
	public function get_phone_number(): ?string {
		return $this->extract_phone_number( $this->merchant->get_account() );
	}

	/**
	 * Update the phone number for the connected Merchant Center account.
	 *
	 * @param string $phone_number The new phone number to add the the Merchant Center account.
	 *
	 * @return string|null The phone number associated with the Merchant Center account or null.
	 * @throws ContentApiException If the Merchant Center account can't be retrieved or updated.
	 */
	public function update_phone_number( string $phone_number ): ?string {
		$account              = $this->merchant->get_account();
		$business_information = $account->getBusinessInformation() ?: new AccountBusinessInformation();
		$business_information->setPhoneNumber( $phone_number );
		$account->setBusinessInformation( $business_information );
		$this->merchant->update_account( $account );
		return $this->extract_phone_number( $account );
	}

	/**
	 * Extract the phone number from the provided Merchant Center account. If the
	 * account has no phone number, an empty string is returned.
	 *
	 * @param Account $account
	 *
	 * @return string|null Null if the account has no phone number.
	 */
	protected function extract_phone_number( Account $account ): ?string {
		$business_information = $account->getBusinessInformation();
		return $business_information ? $business_information->getPhoneNumber() : null;
	}
}
