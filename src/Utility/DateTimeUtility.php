<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use DateTime;
use DateTimeZone;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateTimeUtility
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Utility
 *
 * @since x.x.x
 */
class DateTimeUtility implements Service {

	/**
	 * Convert a timezone offset to the closest matching timezone string.
	 *
	 * @param string $timezone
	 *
	 * @return string
	 *
	 * @throws Exception If the DateTime instantiation fails.
	 */
	public function maybe_convert_tz_string( string $timezone ): string {
		if ( false !== strpos( $timezone, ':' ) ) {
			[ $hours, $minutes ] = explode( ':', $timezone );

			$dst      = (int) ( new DateTime( 'now', new DateTimeZone( $timezone ) ) )->format( 'I' );
			$seconds  = $hours * 60 * 60 + $minutes * 60;
			$tz_name  = timezone_name_from_abbr( '', $seconds, $dst );
			$timezone = $tz_name !== false ? $tz_name : date_default_timezone_get();
		}

		if ( 'UTC' === $timezone ) {
			$timezone = 'Etc/GMT';
		}

		return $timezone;
	}
}
