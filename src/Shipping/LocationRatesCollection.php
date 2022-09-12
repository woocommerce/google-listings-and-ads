<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationRatesCollection
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
abstract class LocationRatesCollection {
	/**
	 * @var LocationRate[]
	 */
	protected $location_rates = [];

	/**
	 * RatesCollection constructor.
	 *
	 * @param LocationRate[] $location_rates
	 */
	public function __construct( array $location_rates = [] ) {
		$this->set_location_rates( $location_rates );
	}

	/**
	 * @return LocationRate[]
	 */
	public function get_location_rates(): array {
		return $this->location_rates;
	}

	/**
	 * @param LocationRate[] $location_rates
	 *
	 * @return LocationRatesCollection
	 */
	public function set_location_rates( array $location_rates ): LocationRatesCollection {
		foreach ( $location_rates as $location_rate ) {
			$this->validate_rate( $location_rate );
		}

		$this->location_rates = $location_rates;
		$this->reset_rates_mappings();

		return $this;
	}

	/**
	 * @param LocationRate $location_rate
	 *
	 * @return LocationRatesCollection
	 */
	public function add_location_rate( LocationRate $location_rate ): LocationRatesCollection {
		$this->validate_rate( $location_rate );

		$this->location_rates[] = $location_rate;
		$this->reset_rates_mappings();

		return $this;
	}

	/**
	 * @param LocationRate $location_rate
	 *
	 * @throws InvalidValue If any of the location rates do not belong to the same country as the one provided for this class.
	 */
	abstract protected function validate_rate( LocationRate $location_rate );

	/**
	 * Reset the internal mappings/groups
	 */
	abstract protected function reset_rates_mappings(): void;
}
