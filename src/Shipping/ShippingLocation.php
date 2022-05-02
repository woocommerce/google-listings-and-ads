<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingLocation
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   x.x.x
 */
class ShippingLocation {
	public const COUNTRY_AREA  = 'country_area';
	public const STATE_AREA    = 'state_area';
	public const POSTCODE_AREA = 'postcode_area';

	/**
	 * @var int
	 */
	protected $google_id;

	/**
	 * @var string
	 */
	protected $country;

	/**
	 * @var string
	 */
	protected $state;

	/**
	 * @var PostcodeRange[]
	 */
	protected $postcodes;

	/**
	 * ShippingLocation constructor.
	 *
	 * @param int                  $google_id
	 * @param string               $country
	 * @param string|null          $state
	 * @param PostcodeRange[]|null $postcodes
	 */
	public function __construct( int $google_id, string $country, ?string $state = null, ?array $postcodes = null ) {
		$this->google_id = $google_id;
		$this->country   = $country;
		$this->state     = $state;
		$this->postcodes = $postcodes;
	}

	/**
	 * @return int
	 */
	public function get_google_id(): int {
		return $this->google_id;
	}

	/**
	 * @return string
	 */
	public function get_country(): string {
		return $this->country;
	}

	/**
	 * @return string|null
	 */
	public function get_state(): ?string {
		return $this->state;
	}

	/**
	 * @return PostcodeRange[]|null
	 */
	public function get_postcodes(): ?array {
		return $this->postcodes;
	}

	/**
	 * Return the postcode group name for this location.
	 *
	 * @return string Serialized string representation of the postcodes, or an empty string if the location doesn't have any postcodes.
	 */
	public function get_postcode_group_name(): string {
		if ( empty( $this->get_postcodes() ) ) {
			return '';
		}

		return sprintf(
			'%s - %s',
			$this->get_country(),
			join( ',', $this->get_postcodes() )
		);
	}

	/**
	 * Return the applicable shipping area for this shipping location. e.g. whether it applies to a whole country, state, or postcodes.
	 *
	 * @return string
	 */
	public function get_applicable_area(): string {
		if ( ! empty( $this->get_postcodes() ) ) {
			// ShippingLocation applies to a select postal code ranges of a country
			return self::POSTCODE_AREA;
		} elseif ( ! empty( $this->get_state() ) ) {
			// ShippingLocation applies to a state/province of a country
			return self::STATE_AREA;
		} else {
			// ShippingLocation applies to a whole country
			return self::COUNTRY_AREA;
		}
	}

	/**
	 * Returns the string representation of this ShippingLocation.
	 *
	 * @return string
	 */
	public function __toString() {
		$code = $this->get_country();
		if ( ! empty( $this->get_postcodes() ) ) {
			// We assume that each postcode is unique within any supported country (a requirement set by Google API).
			// Therefore, there is no need to include the state name in the location string even if it's provided.
			$code .= '::' . join( ',', $this->get_postcodes() );
		} elseif ( ! empty( $this->get_state() ) ) {
			$code .= '_' . $this->get_state();
		}

		return $code;
	}

}
