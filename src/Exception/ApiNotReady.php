<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ApiNotReady
 *
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @since 1.12.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class ApiNotReady extends ExceptionWithResponseData {

	/**
	 * Create a new instance of the exception when an API is not ready and the request needs to be retried.
	 *
	 * @param int $wait Time to wait in seconds.
	 *
	 * @return static
	 */
	public static function retry_after( int $wait ): ApiNotReady {
		return new static(
			__( 'Please retry the request after the indicated number of seconds.', 'google-listings-and-ads' ),
			503,
			null,
			[
				'retry_after' => $wait,
			]
		);
	}
}
