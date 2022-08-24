<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class ServiceRatesCollection
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
class ServiceRatesCollection extends CountryRatesCollection {
	/**
	 * @var string
	 */
	protected $shipping_area;

	/**
	 * @var float|null
	 */
	protected $min_order_amount;

	/**
	 * @var LocationRate[][]
	 */
	protected $class_groups;

	/**
	 * ServiceRatesCollection constructor.
	 *
	 * @param string     $country
	 * @param string     $shipping_area
	 * @param float|null $min_order_amount
	 * @param array      $location_rates
	 */
	public function __construct( string $country, string $shipping_area, ?float $min_order_amount = null, array $location_rates = [] ) {
		$this->shipping_area    = $shipping_area;
		$this->min_order_amount = $min_order_amount;
		parent::__construct( $country, $location_rates );
	}

	/**
	 * @return float|null
	 */
	public function get_min_order_amount(): ?float {
		return $this->min_order_amount;
	}

	/**
	 * @return string
	 */
	public function get_shipping_area(): string {
		return $this->shipping_area;
	}

	/**
	 * Return array of location rates grouped by their applicable shipping classes. Multiple rates might be returned per class.
	 *
	 * @return LocationRate[][] Arrays of location rates grouped by their applicable shipping class. Shipping class name is used as array keys.
	 */
	public function get_rates_grouped_by_shipping_class(): array {
		$this->group_rates_by_shipping_class();

		return $this->class_groups;
	}

	/**
	 * Group the location rates by their applicable shipping classes.
	 */
	public function group_rates_by_shipping_class(): void {
		if ( isset( $this->class_groups ) ) {
			return;
		}
		$this->class_groups = [];

		foreach ( $this->location_rates as $location_rate ) {
			if ( ! empty( $location_rate->get_shipping_rate()->get_applicable_classes() ) ) {
				// For every rate defined in the location_rate, create a new shipping rate and add it to the array
				foreach ( $location_rate->get_shipping_rate()->get_applicable_classes() as $class ) {
					$this->class_groups[ $class ][] = $location_rate;
				}
			} else {
				$this->class_groups[''][] = $location_rate;
			}
		}

		// Sort the groups so that the rate with no shipping class is placed at the end.
		krsort( $this->class_groups );
	}

	/**
	 * Reset the internal mappings/groups
	 */
	protected function reset_rates_mappings(): void {
		parent::reset_rates_mappings();
		unset( $this->class_groups );
	}
}
