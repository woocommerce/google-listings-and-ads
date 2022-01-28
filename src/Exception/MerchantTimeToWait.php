<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantResponseException
 *
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class MerchantTimeToWait extends ExceptionWithResponseData {

	/**
	 * Create a new instance of the exception when a Merchant Center account connection needs to wait.
	 *
	 * @param int $wait Time to wait in seconds.
	 *
	 * @return static
	 */
	public static function retry_after( int $wait ): MerchantTimeToWait {
		return new static(
			__( 'Please retry after the indicated number of seconds to complete the account setup process.', 'google-listings-and-ads' ),
			[
				'retry_after' => $wait,
			],
			503
		);
	}

}
