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

	/** @var RateQuery */
	protected $rate_query;

	/** @var TimeQuery */
	protected $time_query;

	/**
	 * Settings constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container  = $container;
		$this->rate_query = $container->get( RateQuery::class );
		$this->time_query = $container->get( TimeQuery::class );
	}

	/**
	 * Sync the shipping settings with Google.
	 */
	public function sync_shipping() {
		/** @var OptionsInterface $options */
		$options = $this->container->get( OptionsInterface::class );

		$merchant_id = $options->get( OptionsInterface::MERCHANT_ID );
		$account_id  = $merchant_id;
		$times       = $this->get_times();
		$settings    = new ShippingSettings();
		$settings->setAccountId( $account_id );

		$shipping_rates = $this->rate_query->get_results();
		$services       = [];
		foreach ( $shipping_rates as $shipping_rate ) {
			$country = $shipping_rate['country'];

			$service = new Service();
			$service->setActive( true );
			$service->setDeliveryCountry( $country );
			$service->setCurrency( $shipping_rate['currency'] );

			$service->setRateGroups(
				[
					$this->create_rate_group_object( $shipping_rate['currency'], $shipping_rate['rate'] ),
				]
			);

			if ( array_key_exists( $country, $times ) ) {
				$service->setDeliveryTime( $this->create_time_object( intval( $times[ $country ] ) ) );
			}

			$services[] = $service;
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
		$raw = $this->time_query->get_results();

		return wp_list_pluck( $raw, 'time', 'country' );
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
}
