<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery as RateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery as TimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_DeliveryTime as DeliveryTime;
use Google_Service_ShoppingContent_Price as Price;
use Google_Service_ShoppingContent_RateGroup as RateGroup;
use Google_Service_ShoppingContent_Service as Service;
use Google_Service_ShoppingContent_ShippingSettings as ShippingSettings;
use Google_Service_ShoppingContent_Value as Value;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Settings {

	/** @var ContainerInterface */
	protected $container;

	/**
	 * Settings constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Sync the shipping settings with Google.
	 *
	 * @return ShippingSettings
	 */
	public function sync_shipping(): ShippingSettings {
		// Merchant ID and Account ID seem to be the same value.
		$merchant_id = $this->get_options_object()->get( OptionsInterface::MERCHANT_ID );
		$account_id  = $merchant_id;
		$settings    = new ShippingSettings();
		$settings->setAccountId( $account_id );

		$services = [];
		foreach ( $this->get_rates() as ['country' => $country, 'currency' => $currency, 'rate' => $rate] ) {
			$services[] = $this->create_main_service( $country, $currency, $rate );

			if ( $this->has_free_shipping_option() ) {
				$services[] = $this->create_free_shipping_service( $country, $currency );
			}
		}

		$settings->setServices( $services );

		/** @var ShoppingService $content_service */
		$content_service = $this->container->get( ShoppingService::class );

		return $content_service->shippingsettings->update(
			$merchant_id,
			$account_id,
			$settings
		);
	}

	/**
	 * Get shipping time data.
	 *
	 * @return array
	 */
	protected function get_times(): array {
		static $times = null;

		if ( null === $times ) {
			$time_query = $this->container->get( TimeQuery::class );
			$times      = array_column( $time_query->get_results(), 'time', 'country' );
		}

		return $times;
	}

	/**
	 * Get shipping rate data.
	 *
	 * @return array
	 */
	protected function get_rates(): array {
		$rate_query = $this->container->get( RateQuery::class );

		return $rate_query->get_results();
	}

	/**
	 * Create the DeliveryTime object.
	 *
	 * @param int $delivery_days
	 *
	 * @return DeliveryTime
	 */
	protected function create_time_object( int $delivery_days ): DeliveryTime {
		$time = new DeliveryTime();
		$time->setMinHandlingTimeInDays( 0 );
		$time->setMaxHandlingTimeInDays( 0 );
		$time->setMinTransitTimeInDays( $delivery_days );
		$time->setMaxTransitTimeInDays( $delivery_days );

		return $time;
	}

	/**
	 * Create the array of rate groups for the service.
	 *
	 * @param string $currency
	 * @param mixed  $rate
	 *
	 * @return array
	 */
	protected function create_rate_groups( string $currency, $rate ): array {
		return [ $this->create_rate_group_object( $currency, $rate ) ];
	}

	/**
	 * Create a rate group object for the shopping settings.
	 *
	 * @param string $currency
	 * @param mixed  $rate
	 *
	 * @return RateGroup
	 */
	protected function create_rate_group_object( string $currency, $rate ): RateGroup {
		$price = new Price();
		$price->setCurrency( $currency );
		$price->setValue( $rate );

		$value = new Value();
		$value->setFlatRate( $price );

		$rate_group = new RateGroup();
		$rate_group->setSingleValue( $value );

		return $rate_group;
	}

	/**
	 * Determine whether free shipping is offered.
	 *
	 * @return bool
	 */
	protected function has_free_shipping_option(): bool {
		return boolval(
			$this->get_options_object()->get( OptionsInterface::MERCHANT_CENTER )['offers_free_shipping'] ?? false
		);
	}

	/**
	 * @return OptionsInterface
	 */
	protected function get_options_object(): OptionsInterface {
		return $this->container->get( OptionsInterface::class );
	}

	/**
	 * Create the main shipping service object.
	 *
	 * @param string $country
	 * @param string $currency
	 * @param mixed  $rate
	 *
	 * @return Service
	 */
	protected function create_main_service( string $country, string $currency, $rate ): Service {
		$service = new Service();
		$service->setActive( true );
		$service->setDeliveryCountry( $country );
		$service->setCurrency( $currency );
		$service->setName(
			sprintf(
				/* translators: %s is the 2 character country code */
				__( 'Google Listings and Ads generated service - %s', 'google-listings-and-ads' ),
				$country
			)
		);

		$service->setRateGroups( $this->create_rate_groups( $currency, $rate ) );

		$times = $this->get_times();
		if ( array_key_exists( $country, $times ) ) {
			$service->setDeliveryTime( $this->create_time_object( intval( $times[ $country ] ) ) );
		}

		return $service;
	}

	/**
	 * Create a free shipping service.
	 *
	 * @param string $country
	 * @param string $currency
	 *
	 * @return Service
	 */
	protected function create_free_shipping_service( string $country, string $currency ): Service {
		$price = new Price();
		$price->setValue( 0 );
		$price->setCurrency( $currency );

		$service = $this->create_main_service( $country, $currency, 0 );
		$service->setMinimumOrderValue( $price );

		return $service;
	}
}
