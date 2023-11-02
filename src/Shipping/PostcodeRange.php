<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostcodeRange
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 2.1.0
 */
class PostcodeRange {

	/**
	 * @var string
	 */
	protected $start_code;

	/**
	 * @var string
	 */
	protected $end_code;

	/**
	 * PostcodeRange constructor.
	 *
	 * @param string      $start_code Beginning of the range.
	 * @param string|null $end_code   End of the range.
	 */
	public function __construct( string $start_code, ?string $end_code = null ) {
		$this->start_code = $start_code;
		$this->end_code   = $end_code;
	}

	/**
	 * @return string
	 */
	public function get_start_code(): string {
		return $this->start_code;
	}

	/**
	 * @return string|null
	 */
	public function get_end_code(): ?string {
		return $this->end_code;
	}

	/**
	 * Returns a PostcodeRange object from a string representation of the postcode.
	 *
	 * @param string $postcode String representation of the postcode. If it's a range it should be separated by "...". E.g. "12345...12345".
	 *
	 * @return PostcodeRange
	 */
	public static function from_string( string $postcode ): PostcodeRange {
		$postcode_range = explode( '...', $postcode );
		if ( 2 === count( $postcode_range ) ) {
			return new PostcodeRange( $postcode_range[0], $postcode_range[1] );
		}

		return new PostcodeRange( $postcode );
	}

	/**
	 * Returns the string representation of this postcode.
	 *
	 * @return string
	 */
	public function __toString() {
		if ( ! empty( $this->end_code ) ) {
			return "$this->start_code...$this->end_code";
		}

		return $this->start_code;
	}
}
