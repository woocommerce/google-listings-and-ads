<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class CountryRatesCollection
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
class CountryRatesCollection extends LocationRatesCollection {
	/**
	 * @var string
	 */
	protected $country;

	/**
	 * @var ServiceRatesCollection[]
	 */
	protected $services_groups;

	/**
	 * CountryRatesCollection constructor.
	 *
	 * @param string         $country
	 * @param LocationRate[] $location_rates
	 */
	public function __construct( string $country, array $location_rates = [] ) {
		$this->country = $country;
		parent::__construct( $location_rates );
	}

	/**
	 * @return string
	 */
	public function get_country(): string {
		return $this->country;
	}

	/**
	 * Return collections of location rates grouped into shipping services.
	 *
	 * @return ServiceRatesCollection[]
	 */
	public function get_rates_grouped_by_service(): array {
		$this->group_rates_by_service();

		return array_values( $this->services_groups );
	}

	/**
	 * Groups the location rates into collections of rates based on how they fit into Merchant Center services.
	 */
	protected function group_rates_by_service(): void {
		if ( isset( $this->services_groups ) ) {
			return;
		}
		$this->services_groups = [];

		foreach ( $this->location_rates as $location_rate ) {
			$country          = $location_rate->get_location()->get_country();
			$shipping_area    = $location_rate->get_location()->get_applicable_area();
			$min_order_amount = $location_rate->get_shipping_rate()->get_min_order_amount();

			// Group rates by their applicable country and affecting shipping area
			$service_key = $country . $shipping_area;
			// If the rate has a min order amount constraint, then it should be under a new service
			if ( $location_rate->get_shipping_rate()->has_min_order_amount() ) {
				$service_key .= $min_order_amount;
			}

			if ( ! isset( $this->services_groups[ $service_key ] ) ) {
				$this->services_groups[ $service_key ] = new ServiceRatesCollection(
					$country,
					$shipping_area,
					$min_order_amount,
					[]
				);
			}

			$this->services_groups[ $service_key ]->add_location_rate( $location_rate );
		}
	}

	/**
	 * @param LocationRate $location_rate
	 *
	 * @throws InvalidValue If any of the location rates do not belong to the same country as the one provided for this class.
	 */
	protected function validate_rate( LocationRate $location_rate ) {
		if ( $this->country !== $location_rate->get_location()->get_country() ) {
			throw new InvalidValue( 'All location rates must be in the same country as the one provided for this collection.' );
		}
	}

	/**
	 * Reset the internal mappings/groups
	 */
	protected function reset_rates_mappings(): void {
		unset( $this->services_groups );
	}
}
