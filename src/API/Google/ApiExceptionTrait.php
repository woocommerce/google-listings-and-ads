<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\ApiCore\ApiException;

/**
 * Trait ApiExceptionTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait ApiExceptionTrait {

	/**
	 * Check if the ApiException contains a specific error.
	 *
	 * @param ApiException $exception  Exception to check.
	 * @param string       $error_code Error code we are checking.
	 *
	 * @return bool
	 */
	protected function has_api_exception_error( ApiException $exception, string $error_code ): bool {
		$meta = $exception->getMetadata();
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			return false;
		}

		foreach ( $meta as $data ) {
			if ( empty( $data['errors'] || ! is_array( $data['errors'] ) ) ) {
				continue;
			}

			foreach ( $data['errors'] as $error ) {
				if ( in_array( $error_code, $error['errorCode'], true ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
