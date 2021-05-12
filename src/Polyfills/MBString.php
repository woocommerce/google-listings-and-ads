<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Polyfills;

/**
 * Class Multi Byte String
 *
 * Based off of: https://github.com/symfony/polyfill-mbstring
 *
 * For the full copyright and license information, please view the LICENSE
 * https://github.com/symfony/polyfill-mbstring/blob/v1.22.1/LICENSE
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Polyfills
 */
final class MBString {
	/**
	 * Default list of supported encodings.
	 *
	 * @var array
	 */
	private static $encoding_list = [ 'ASCII', 'UTF-8' ];

	/**
	 * Default encoding type.
	 *
	 * @var string
	 */
	private static $internal_encoding = 'UTF-8';

	/**
	 * Convert encoding.
	 *
	 * @param array|string      $string
	 * @param string            $to_encoding
	 * @param array|string|null $from_encoding
	 */
	public static function mb_convert_encoding( $string, $to_encoding, $from_encoding = null ) {
		if ( \is_array( $from_encoding ) || false !== strpos( $from_encoding, ',' ) ) {
			$from_encoding = self::mb_detect_encoding( $string, $from_encoding );
		} else {
			$from_encoding = self::get_encoding( $from_encoding );
		}

		$to_encoding = self::get_encoding( $to_encoding );

		if ( 'BASE64' === $from_encoding ) {
			$string        = base64_decode( $string );
			$from_encoding = $to_encoding;
		}

		if ( 'BASE64' === $to_encoding ) {
			return base64_encode( $string );
		}

		if ( 'HTML-ENTITIES' === $to_encoding || 'HTML' === $to_encoding ) {
			if ( 'HTML-ENTITIES' === $from_encoding || 'HTML' === $from_encoding ) {
				$from_encoding = 'Windows-1252';
			}
			if ( 'UTF-8' !== $from_encoding ) {
				$string = \iconv( $from_encoding, 'UTF-8//IGNORE', $string );
			}

			return preg_replace_callback( '/[\x80-\xFF]+/', [ __CLASS__, 'html_encoding_callback' ], $string );
		}

		if ( 'HTML-ENTITIES' === $from_encoding ) {
			$string        = html_entity_decode( $string, \ENT_COMPAT, 'UTF-8' );
			$from_encoding = 'UTF-8';
		}

		return \iconv( $from_encoding, $to_encoding . '//IGNORE', $string );
	}

	/**
	 * Check if strings are valid for the specified encoding.
	 *
	 * @param array|string|null $value
	 * @param string|null       $encoding
	 */
	public static function mb_check_encoding( $value = null, $encoding = null ) {
		if ( null === $encoding ) {
			if ( null === $value ) {
				return false;
			}
			$encoding = self::$internal_encoding;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return self::mb_detect_encoding( $value, [ $encoding ] ) || false !== @\iconv( $encoding, $encoding, $value );
	}

	/**
	 * Detect character encoding.
	 *
	 * @param string            $string
	 * @param array|string|null $encoding_list
	 * @param boolean           $strict
	 *
	 * @return string|false
	 */
	public static function mb_detect_encoding( $string, $encoding_list = null, $strict = false ) {
		if ( null === $encoding_list ) {
			$encoding_list = self::$encoding_list;
		} else {
			if ( ! \is_array( $encoding_list ) ) {
				$encoding_list = array_map( 'trim', explode( ',', $encoding_list ) );
			}
			$encoding_list = array_map( 'strtoupper', $encoding_list );
		}

		foreach ( $encoding_list as $enc ) {
			switch ( $enc ) {
				case 'ASCII':
					if ( ! preg_match( '/[\x80-\xFF]/', $string ) ) {
						return $enc;
					}
					break;

				case 'UTF8':
				case 'UTF-8':
					if ( preg_match( '//u', $string ) ) {
						return 'UTF-8';
					}
					break;

				default:
					if ( 0 === strncmp( $enc, 'ISO-8859-', 9 ) ) {
						return $enc;
					}
			}
		}

		return false;
	}

	/**
	 * Get string length.
	 *
	 * @param string      $string
	 * @param string|null $encoding
	 * @return int
	 */
	public static function mb_strlen( $string, $encoding = null ): int {
		$encoding = self::get_encoding( $encoding );
		if ( 'CP850' === $encoding || 'ASCII' === $encoding ) {
			return \strlen( $string );
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return @\iconv_strlen( $string, $encoding );
	}

	/**
	 * Encode HTML entities.
	 *
	 * @param array $m
	 *
	 * @return string
	 */
	private static function html_encoding_callback( array $m ) {
		$i        = 1;
		$entities = '';
		$m        = unpack( 'C*', htmlentities( $m[0], \ENT_COMPAT, 'UTF-8' ) );

		while ( isset( $m[ $i ] ) ) {
			if ( 0x80 > $m[ $i ] ) {
				$entities .= \chr( $m[ $i++ ] );
				continue;
			}
			if ( 0xF0 <= $m[ $i ] ) {
				$c = ( ( $m[ $i++ ] - 0xF0 ) << 18 ) + ( ( $m[ $i++ ] - 0x80 ) << 12 ) + ( ( $m[ $i++ ] - 0x80 ) << 6 ) + $m[ $i++ ] - 0x80;
			} elseif ( 0xE0 <= $m[ $i ] ) {
				$c = ( ( $m[ $i++ ] - 0xE0 ) << 12 ) + ( ( $m[ $i++ ] - 0x80 ) << 6 ) + $m[ $i++ ] - 0x80;
			} else {
				$c = ( ( $m[ $i++ ] - 0xC0 ) << 6 ) + $m[ $i++ ] - 0x80;
			}

			$entities .= '&#' . $c . ';';
		}

		return $entities;
	}

	/**
	 * Return formatted encoding string.
	 *
	 * @param string $encoding
	 *
	 * @return string
	 */
	private static function get_encoding( $encoding ) {
		if ( null === $encoding ) {
			return self::$internal_encoding;
		}

		if ( 'UTF-8' === $encoding ) {
			return 'UTF-8';
		}

		$encoding = strtoupper( $encoding );

		if ( '8BIT' === $encoding || 'BINARY' === $encoding ) {
			return 'CP850';
		}

		if ( 'UTF8' === $encoding ) {
			return 'UTF-8';
		}

		return $encoding;
	}
}
