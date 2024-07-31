<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Trait Utilities
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
trait Utilities {

	use OptionsAwareTrait;

	/**
	 * Does the store have (x) orders
	 *
	 * @param  integer $count  Number of orders to check for
	 * @param  array   $status Order statuses to check for
	 * @return boolean
	 */
	protected function has_orders( $count = 5, $status = [ 'wc-completed' ] ): bool {
		$args = [
			'status'  => $status,
			'limit'   => $count,
			'return'  => 'ids',
			'orderby' => 'date',
			'order'   => 'ASC',
		];

		return $count === count( wc_get_orders( $args ) );
	}

	/**
	 * Test how long GLA has been active.
	 *
	 * @param int $seconds Time in seconds to check.
	 * @return bool Whether or not GLA has been active for $seconds.
	 */
	protected function gla_active_for( $seconds ): bool {
		$gla_installed = $this->options->get( OptionsInterface::INSTALL_TIMESTAMP, false );

		if ( false === $gla_installed ) {
			return false;
		}

		return ( ( time() - $gla_installed ) >= $seconds );
	}

	/**
	 * Test how long GLA has been setup for.
	 *
	 * @param int $seconds Time in seconds to check.
	 * @return bool Whether or not GLA has been active for $seconds.
	 */
	protected function gla_setup_for( $seconds ): bool {
		$gla_completed_setup = $this->options->get( OptionsInterface::MC_SETUP_COMPLETED_AT, false );

		if ( false === $gla_completed_setup ) {
			return false;
		}

		return ( ( time() - $gla_completed_setup ) >= $seconds );
	}

	/**
	 * Is Jetpack connected?
	 *
	 * @since 1.12.5
	 *
	 * @return boolean
	 */
	protected function is_jetpack_connected(): bool {
		return boolval( $this->options->get( OptionsInterface::JETPACK_CONNECTED, false ) );
	}

	/**
	 * Encode data to Base64URL
	 *
	 * @since 2.8.0
	 *
	 * @param string $data The string that will be base64 URL encoded.
	 *
	 * @return string
	 */
	protected function base64url_encode( $data ): string {
		$b64 = base64_encode( $data );

		// Convert Base64 to Base64URL by replacing "+" with "-" and "/" with "_"
		$url = strtr( $b64, '+/', '-_' );

		// Remove padding character from the end of line and return the Base64URL result
		return rtrim( $url, '=' );
	}

	/**
	 * Decode Base64URL string
	 *
	 * @since 2.8.0
	 *
	 * @param string $data The data that will be base64 URL encoded.
	 *
	 * @return boolean|string
	 */
	protected function base64url_decode( $data ): string {
		// Convert Base64URL to Base64 by replacing "-" with "+" and "_" with "/"
		$b64 = strtr( $data, '-_', '+/' );

		// Decode Base64 string and return the original data
		return base64_decode( $b64 );
	}
}
