<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery as RateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery as TimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_DeliveryTime;
use Google_Service_ShoppingContent_Service;
use Google_Service_ShoppingContent_ShippingSettings;
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

		$account_id  = 12345; // todo: change this to real value.
		$merchant_id = $options->get( OptionsInterface::MERCHANT_ID );
		$times       = $this->get_times();
		$settings    = new Google_Service_ShoppingContent_ShippingSettings();
		$settings->setAccountId( $account_id );

		$shipping_rates = $this->rate_query->set_limit( 100 )->get_results();
		$services       = [];
		foreach ( $shipping_rates as $shipping_rate ) {
			$country = $shipping_rate['country'];

			$service = new Google_Service_ShoppingContent_Service();
			$service->setActive( true );
			$service->setDeliveryCountry( $country );

			if ( array_key_exists( $country, $times ) ) {
				$time = new Google_Service_ShoppingContent_DeliveryTime();
				$time->setMinTransitTimeInDays( $times[ $country ] );
				$time->setMaxTransitTimeInDays( $times[ $country ] );
				$service->setDeliveryTime( $time );
			}

			$services[] = $service;
		}

		$settings->setServices( $services );

		/** @var ShoppingService $content_service */
		$content_service = $this->container->get( ShoppingService::class );
		$content_service->shippingsettings->update(
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
}
