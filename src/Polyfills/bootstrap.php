<?php
declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\Polyfills\MBString;

if ( ! function_exists( 'mb_convert_encoding' ) ) {
	/**
	 * Convert encoding.
	 *
	 * @param array|string      $string
	 * @param string            $to_encoding
	 * @param array|string|null $from_encoding
	 */
	function mb_convert_encoding( $string, $to_encoding, $from_encoding = null ) {
		return MBString::mb_convert_encoding( $string, $to_encoding, $from_encoding );
	}
}

if ( ! function_exists( 'mb_check_encoding' ) ) {
	/**
	 * Check if strings are valid for the specified encoding.
	 *
	 * @param array|string|null $value
	 * @param string|null       $encoding
	 */
	function mb_check_encoding( $value = null, $encoding = null ) {
		return MBString::mb_check_encoding( $value, $encoding );
	}
}

if ( ! function_exists( 'mb_strlen' ) ) {
	/**
	 * Get string length.
	 *
	 * @param string      $string
	 * @param string|null $encoding
	 * @return int
	 */
	function mb_strlen( $string, $encoding = null ): int {
		return MBString::mb_strlen( $string, $encoding );
	}
}

if ( ! function_exists( 'mb_substr' ) ) {
	/**
	 * Get part of string.
	 *
	 * @param string      $string
	 * @param int         $start
	 * @param int|null    $length
	 * @param string|null $encoding
	 *
	 * @return string
	 *
	 * @since 1.4.1
	 */
	function mb_substr( $string, $start, $length = null, $encoding = null ) {
		return MBString::mb_substr( $string, $start, $length, $encoding );
	}
}
