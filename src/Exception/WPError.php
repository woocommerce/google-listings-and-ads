<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use RuntimeException;
use WP_Error;

/**
 * Class WPError.
 *
 * Used to convert a WP_Error object to a thrown exception.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class WPError extends RuntimeException implements GoogleListingsAndAdsException {

	/**
	 * Convert a WP_Error object to a throwable exception.
	 *
	 * @param WP_Error $error The error object.
	 *
	 * @return static
	 */
	public static function from_error( WP_Error $error ) {
		$message     = $error->get_error_message();
		$code        = $error->get_error_code();
		$string_code = '';
		if ( ! is_numeric( $code ) ) {
			$string_code = $code;
			$code        = 0;
		}

		return new static(
			sprintf( 'A WP Error was generated. Code: "%s" Message: "%s".', $string_code, $message ),
			$code
		);
	}
}
