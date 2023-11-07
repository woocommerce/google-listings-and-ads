<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRegion
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 2.1.0
 */
class ShippingRegion {

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $country;

	/**
	 * @var PostcodeRange[]
	 */
	protected $postcode_ranges;

	/**
	 * ShippingRegion constructor.
	 *
	 * @param string          $id
	 * @param string          $country
	 * @param PostcodeRange[] $postcode_ranges
	 */
	public function __construct( string $id, string $country, array $postcode_ranges ) {
		$this->id              = $id;
		$this->country         = $country;
		$this->postcode_ranges = $postcode_ranges;
	}

	/**
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function get_country(): string {
		return $this->country;
	}

	/**
	 * @return PostcodeRange[]
	 */
	public function get_postcode_ranges(): array {
		return $this->postcode_ranges;
	}

	/**
	 * Generate a random ID for the region.
	 *
	 * For privacy reasons, the region ID value must be a randomized set of numbers (minimum 6 digits)
	 *
	 * @return string
	 *
	 * @throws \Exception If generating a random ID fails.
	 *
	 * @link https://support.google.com/merchants/answer/9698880?hl=en#requirements
	 */
	public static function generate_random_id(): string {
		return (string) random_int( 100000, PHP_INT_MAX );
	}

	/**
	 * Returns the string representation of this object.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->get_country() . join( ',', $this->get_postcode_ranges() );
	}
}
