<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Account;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountAddress;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountBusinessInformation;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountStatus;

/**
 * Trait MerchantTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait MerchantTrait {

	protected $valid_account_phone_number = '+18008675309';
	protected $valid_account_id           = '123581321';

	/**
	 * Get a mocked instance of GoogleException that occurs within the runtime of
	 * Google library.
	 *
	 * @link https://github.com/googleapis/google-api-php-client/blob/v2.15.0/src/Service/Resource.php#L86-L175
	 *
	 * @param string $message Error message
	 *
	 * @return GoogleException
	 */
	public function get_google_exception( string $message = 'Missing required params' ): GoogleException {
		return new GoogleException( $message );
	}

	/**
	 * Get a mocked instance of GoogleServiceException that occurs on the Google Shopping Content
	 * service side and then is transformed within `Google\Http\REST::decodeHttpResponse` method.
	 *
	 * @link https://github.com/googleapis/google-api-php-client/blob/v2.15.0/src/Http/REST.php#L119-L135
	 *
	 * @param int    $code    Error code.
	 * @param string $message Error message
	 *
	 * @return GoogleServiceException
	 */
	public function get_google_service_exception( int $code = 400, string $message = 'Invalid query' ): GoogleServiceException {
		$error = [
			'reason'  => 'invalid',
			'message' => $message,
		];
		return new GoogleServiceException( 'response body', $code, null, [ $error ] );
	}

	public function get_empty_account(): Account {
		return new Account();
	}

	public function get_sample_address(): AccountAddress {
		$address = new AccountAddress();
		$address->setCountry( 'US' );
		$address->setRegion( 'California' );
		$address->setLocality( 'San Fransisco' );
		$address->setStreetAddress( '123 Main St.' );
		$address->setPostalCode( '12345' );

		return $address;
	}

	public function get_valid_account(): Account {
		$account = new Account();
		$account->setBusinessInformation( $this->get_valid_business_info() );

		return $account;
	}

	public function get_valid_business_info(): AccountBusinessInformation {
		$business_info = new AccountBusinessInformation();
		$business_info->setPhoneNumber( $this->valid_account_phone_number );
		$business_info->setPhoneVerificationStatus( 'VERIFIED' );
		$business_info->setAddress( $this->get_sample_address() );

		return $business_info;
	}

	public function get_account_with_url( string $url ): Account {
		$account = new Account();
		$account->setWebsiteUrl( $url );

		return $account;
	}

	public function get_status_website_claimed( bool $claimed = true ): AccountStatus {
		$status = new AccountStatus();
		$status->setWebsiteClaimed( $claimed );

		return $status;
	}

}
