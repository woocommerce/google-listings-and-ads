<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountReconnect
 *
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @since 1.12.5
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class AccountReconnect extends ExceptionWithResponseData {

	/**
	 * Create a new instance of the exception when the Jetpack account is not connected.
	 *
	 * @return static
	 */
	public static function jetpack_disconnected(): AccountReconnect {
		return new static(
			__( 'Please reconnect your Jetpack account.', 'google-listings-and-ads' ),
			401,
			null,
			[
				'status' => 401,
				'code'   => 'JETPACK_DISCONNECTED',
			]
		);
	}

	/**
	 * Create a new instance of the exception when the Google account is not connected.
	 *
	 * @return static
	 */
	public static function google_disconnected(): AccountReconnect {
		return new static(
			__( 'Please reconnect your Google account.', 'google-listings-and-ads' ),
			401,
			null,
			[
				'status' => 401,
				'code'   => 'GOOGLE_DISCONNECTED',
			]
		);
	}
}
