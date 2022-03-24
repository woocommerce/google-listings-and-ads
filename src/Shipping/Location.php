<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class Location
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since x.x.x
 */
class Location {

	/**
	 * @var string
	 */
	protected $country;

	/**
	 * @var string
	 */
	protected $state;

	/**
	 * @var string[]
	 */
	protected $postcodes;

	/**
	 * Location constructor.
	 *
	 * @param string        $country
	 * @param string|null   $state
	 * @param string[]|null $postcodes
	 */
	public function __construct( string $country, ?string $state = null, ?array $postcodes = null ) {
		$this->country   = $country;
		$this->state     = $state;
		$this->postcodes = $postcodes;
	}


	/**
	 * @return string
	 */
	public function get_country(): string {
		return $this->country;
	}

	/**
	 * @param string $country
	 *
	 * @return Location
	 */
	public function set_country( string $country ): Location {
		$this->country = $country;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function get_state(): ?string {
		return $this->state;
	}

	/**
	 * @param string|null $state
	 *
	 * @return Location
	 */
	public function set_state( ?string $state ): Location {
		$this->state = $state;

		return $this;
	}

	/**
	 * @return string[]|null
	 */
	public function get_postcodes(): ?array {
		return $this->postcodes;
	}

	/**
	 * @param string[]|null $postcodes
	 *
	 * @return Location
	 */
	public function set_postcodes( ?array $postcodes ): Location {
		$this->postcodes = $postcodes;

		return $this;
	}

	/**
	 * Returns the string representation of this Location.
	 *
	 * @return string
	 */
	public function __toString() {
		$code = $this->get_country();
		if ( ! empty( $this->get_state() ) ) {
			$code .= '_' . $this->get_state();
		}

		return join(
			'::',
			[
				$code,
				! empty( $this->get_postcodes() ) ? join( ',', $this->get_postcodes() ) : '',
			]
		);
	}

}
