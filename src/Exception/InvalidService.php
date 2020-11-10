<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

/**
 * InvalidService class.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidService extends InvalidArgumentException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception for a service class name that is
	 * not recognized.
	 *
	 * @param string|object $service Class name of the service that was not recognized.
	 *
	 * @return static
	 */
	public static function from_service( $service ) {
		$message = sprintf(
			'The service "%s" cannot be registered because it is not recognized.',
			is_object( $service ) ? get_class( $service ) : (string) $service
		);

		return new static( $message );
	}

	/**
	 * Create a new instance of the exception for a service identifier that is
	 * not recognized.
	 *
	 * @param string $service_id Identifier of the service that is not being recognized.
	 *
	 * @return static
	 */
	public static function from_service_id( string $service_id ) {
		$message = sprintf( 'The service ID "%s" cannot be retrieved because is not recognized.', $service_id );

		return new static( $message );
	}
}
