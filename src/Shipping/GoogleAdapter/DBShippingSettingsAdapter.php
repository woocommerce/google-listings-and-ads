<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Price;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\RateGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Service as GoogleShippingService;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBShippingSettingsAdapter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
class DBShippingSettingsAdapter extends AbstractShippingSettingsAdapter {
	/**
	 * Parses the already validated input data and maps the provided shipping rates into MC shipping settings.
	 *
	 * @param array $data Validated data.
	 */
	protected function map_gla_data( array $data ): void {
		$this->map_db_rates( $data['db_rates'] );
	}

	/**
	 * Validates the input array provided to this class.
	 *
	 * @param array $data
	 *
	 * @throws InvalidValue When the required parameters are not provided, or they are invalid.
	 *
	 * @link AbstractShippingSettingsAdapter::mapTypes() The $data input comes from this method.
	 */
	protected function validate_gla_data( array $data ): void {
		parent::validate_gla_data( $data );

		if ( empty( $data['db_rates'] ) || ! is_array( $data['db_rates'] ) ) {
			throw new InvalidValue( 'The value of "db_rates" must be a non empty associated array of shipping rates.' );
		}
	}

	/**
	 * Remove the extra data we added to the input array since the MC API doesn't expect them (and it will fail).
	 *
	 * @param array $data
	 */
	protected function unset_gla_data( array &$data ): void {
		unset( $data['db_rates'] );
		parent::unset_gla_data( $data );
	}

	/**
	 * Map the shipping rates stored for each country in DB to MC shipping settings.
	 *
	 * @param array[] $db_rates
	 *
	 * @return void
	 */
	protected function map_db_rates( array $db_rates ) {
		$services = [];
		foreach ( $db_rates as ['country' => $country, 'rate' => $rate, 'options' => $options] ) {
			// No negative rates.
			if ( $rate < 0 ) {
				continue;
			}

			$service = $this->create_shipping_service( $country, $this->currency, (float) $rate );

			if ( isset( $options['free_shipping_threshold'] ) ) {
				$minimum_order_value = (float) $options['free_shipping_threshold'];

				if ( $rate > 0 ) {
					// Add a conditional free-shipping service if the current rate is not free.
					$services[] = $this->create_conditional_free_shipping_service( $country, $this->currency, $minimum_order_value );
				} else {
					// Set the minimum order value if the current rate is free.
					$service->setMinimumOrderValue(
						new Price(
							[
								'value'    => $minimum_order_value,
								'currency' => $this->currency,
							]
						)
					);
				}
			}

			$services[] = $service;
		}

		$this->setServices( $services );
	}

	/**
	 * Create a rate group object for the shopping settings.
	 *
	 * @param string $currency
	 * @param float  $rate
	 *
	 * @return RateGroup
	 */
	protected function create_rate_group_object( string $currency, float $rate ): RateGroup {
		$price = new Price();
		$price->setCurrency( $currency );
		$price->setValue( $rate );

		$value = new Value();
		$value->setFlatRate( $price );

		$rate_group = new RateGroup();

		$rate_group->setSingleValue( $value );

		$name = sprintf(
		/* translators: %1 is the shipping rate, %2 is the currency (e.g. USD) */
			__( 'Flat rate - %1$s %2$s', 'google-listings-and-ads' ),
			$rate,
			$currency
		);

		$rate_group->setName( $name );

		return $rate_group;
	}

	/**
	 * Create a shipping service object.
	 *
	 * @param string $country
	 * @param string $currency
	 * @param float  $rate
	 *
	 * @return GoogleShippingService
	 */
	protected function create_shipping_service( string $country, string $currency, float $rate ): GoogleShippingService {
		$unique  = sprintf( '%04x', mt_rand( 0, 0xffff ) );
		$service = new GoogleShippingService();
		$service->setActive( true );
		$service->setDeliveryCountry( $country );
		$service->setCurrency( $currency );
		$service->setName(
			sprintf(
			/* translators: %1 is a random 4-digit string, %2 is the rate, %3 is the currency, %4 is the country code  */
				__( '[%1$s] Google for WooCommerce generated service - %2$s %3$s to %4$s', 'google-listings-and-ads' ),
				$unique,
				$rate,
				$currency,
				$country
			)
		);

		$service->setRateGroups( [ $this->create_rate_group_object( $currency, $rate ) ] );
		$service->setDeliveryTime( $this->get_delivery_time( $country ) );

		return $service;
	}

	/**
	 * Create a free shipping service.
	 *
	 * @param string $country
	 * @param string $currency
	 * @param float  $minimum_order_value
	 *
	 * @return GoogleShippingService
	 */
	protected function create_conditional_free_shipping_service( string $country, string $currency, float $minimum_order_value ): GoogleShippingService {
		$service = $this->create_shipping_service( $country, $currency, 0 );

		// Set the minimum order value to be eligible for free shipping.
		$service->setMinimumOrderValue(
			new Price(
				[
					'value'    => $minimum_order_value,
					'currency' => $currency,
				]
			)
		);

		return $service;
	}
}
