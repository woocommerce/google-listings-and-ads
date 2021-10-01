<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateTimeUtility
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Utility
 *
 * @since 1.5.0
 */
class DateTimeUtility implements Service {

	/**
	 * Convert a timezone offset to the closest matching timezone string.
	 *
	 * @param string $timezone
	 *
	 * @return string
	 */
	public function maybe_convert_tz_string( string $timezone ): string {
		if ( preg_match( '/^([+-]\d{1,2}):?(\d{1,2})$/', $timezone, $matches ) ) {
			[ $timezone, $hours, $minutes ] = $matches;

			$sign    = (int) $hours >= 0 ? 1 : - 1;
			$seconds = $sign * ( absint( $hours ) * 60 * 60 + absint( $minutes ) * 60 );
			$tz_name = timezone_name_from_abbr( '', $seconds, 0 );

			$timezone = $tz_name !== false ? $tz_name : date_default_timezone_get();
		}

		if ( 'UTC' === $timezone ) {
			$timezone = 'Etc/GMT';
		}

		return $timezone;
	}
}
