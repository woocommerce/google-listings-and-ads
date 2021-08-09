<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantApiException
 *
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @since 1.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class MerchantApiException extends RuntimeException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when a Merchant Center account can't be retrieved.
	 *
	 * @param int $code The Exception code.
	 *
	 * @return static
	 */
	public static function account_retrieve_failed( int $code = 0 ): MerchantApiException {
		return new static(
			__( 'Unable to retrieve Merchant Center account.', 'google-listings-and-ads' ),
			$code
		);
	}

	/**
	 * Create a new instance of the exception when a Merchant Center account can't be update.
	 *
	 * @param int $code The Exception code.
	 *
	 * @return static
	 */
	public static function account_update_failed( int $code = 0 ): MerchantApiException {
		return new static(
			__( 'Unable to update Merchant Center account.', 'google-listings-and-ads' ),
			$code
		);
	}
}
