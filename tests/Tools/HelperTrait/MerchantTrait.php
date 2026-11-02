<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountAddress;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountBusinessInformation;

/**
 * Trait MerchantTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait MerchantTrait {

	/**
	 * Get a mocked instance of GoogleServiceException that occurs on the Google API
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

	public function get_sample_address(): AccountAddress {
		$address = new AccountAddress();
		$address->setCountry( 'US' );
		$address->setRegion( 'California' );
		$address->setLocality( 'San Francisco' );
		$address->setStreetAddress( '123 Main St.' );
		$address->setPostalCode( '12345' );

		return $address;
	}

	public function get_valid_business_info(): AccountBusinessInformation {
		$business_info = new AccountBusinessInformation();
		$business_info->setAddress( $this->get_sample_address() );

		return $business_info;
	}
}
