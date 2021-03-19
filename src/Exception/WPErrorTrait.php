<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use Exception;
use Throwable;
use WP_Error;

/**
 * Trait WPErrorTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
trait WPErrorTrait {

	/**
	 * Check for a WP_Error object and throw an exception if we found one.
	 *
	 * @param mixed|WP_Error $maybe_error
	 *
	 * @throws WPError When the object is a WP_Error.
	 */
	protected function check_for_wp_error( $maybe_error ) {
		if ( ! $maybe_error instanceof WP_Error ) {
			return;
		}

		if ( $maybe_error->has_errors() ) {
			throw WPError::from_error( $maybe_error );
		}
	}

	/**
	 * Create a WP_Error from an exception.
	 *
	 * @param Throwable $e
	 * @param string    $code
	 * @param array     $data
	 *
	 * @return WP_Error
	 */
	protected function error_from_exception( Throwable $e, string $code, array $data = [] ): WP_Error {
		return new WP_Error( $code, $e->getMessage(), $data );
	}

	/**
	 * Try to decode a JSON string.
	 *
	 * @param string $message
	 *
	 * @return array
	 * @throws Exception When the JSON could not be decoded.
	 */
	protected function json_decode_message( string $message ): array {
		$decoded = json_decode( $message, true );
		if ( null === $decoded || JSON_ERROR_NONE !== json_last_error() ) {
			throw new Exception( 'Could not decode JSON' );
		}

		return $decoded;
	}
}
