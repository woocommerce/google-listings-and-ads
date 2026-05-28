<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\DBShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use ReflectionMethod;

/**
 * Class SettingsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class SettingsTest extends UnitTest {

	/** @var OptionsInterface */
	private $options;

	/** @var WC */
	private $wc;

	/** @var ShippingTimeQuery */
	private $shipping_time_query;

	/** @var ShippingRateQuery */
	private $shipping_rate_query;

	/** @var TargetAudience */
	private $target_audience;

	/** @var ShippingZone */
	private $shipping_zone;

	/** @var Settings */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		$this->options             = $this->createMock( OptionsInterface::class );
		$this->wc                  = $this->createMock( WC::class );
		$this->shipping_time_query = $this->createMock( ShippingTimeQuery::class );
		$this->shipping_rate_query = $this->createMock( ShippingRateQuery::class );
		$this->target_audience     = $this->createMock( TargetAudience::class );
		$this->shipping_zone       = $this->createMock( ShippingZone::class );

		$container = new Container();
		$container->addShared( OptionsInterface::class, $this->options );
		$container->addShared( WC::class, $this->wc );
		$container->addShared( ShippingTimeQuery::class, $this->shipping_time_query );
		$container->addShared( ShippingRateQuery::class, $this->shipping_rate_query );
		$container->addShared( TargetAudience::class, $this->target_audience );
		$container->addShared( ShippingZone::class, $this->shipping_zone );

		$this->wc->method( 'get_woocommerce_currency' )->willReturn( 'USD' );

		$this->settings = new Settings();
		$this->settings->set_container( $container );
	}

	public function test_automatic_mode_routes_through_wc_adapter() {
		$this->options
			->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::MERCHANT_CENTER, null, [ 'shipping_rate' => 'automatic' ] ],
					[ OptionsInterface::TARGET_AUDIENCE, null, [ 'countries' => [ 'US' ] ] ],
					[ OptionsInterface::MERCHANT_ID, null, 12345 ],
				]
			);

		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )->willReturn( [] );
		// Settings::get_shipping_times() memoises in a static variable, so the
		// fixture has to cover every country touched across the whole test class.
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( $this->all_delivery_times() );
		// If automatic mode is correctly gated, the DB rate query must never be touched.
		$this->shipping_rate_query->expects( $this->never() )->method( 'get_results' );

		$result = $this->invoke_generate_shipping_settings();

		$this->assertInstanceOf( WCShippingSettingsAdapter::class, $result );
		$this->assertNotInstanceOf( DBShippingSettingsAdapter::class, $result );
	}

	public function test_flat_mode_routes_through_db_adapter_with_db_rows_untouched() {
		$db_rows = [
			[
				'country'  => 'US',
				'currency' => 'USD',
				'rate'     => 10,
				'options'  => [],
			],
			[
				'country'  => 'FR',
				'currency' => 'EUR',
				'rate'     => 8,
				'options'  => [],
			],
		];

		$this->options
			->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::MERCHANT_CENTER, null, [ 'shipping_rate' => 'flat' ] ],
					[ OptionsInterface::TARGET_AUDIENCE, null, [ 'countries' => [ 'US', 'FR' ] ] ],
					[ OptionsInterface::MERCHANT_ID, null, 12345 ],
				]
			);

		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn( $this->all_delivery_times() );
		$this->shipping_rate_query->method( 'get_results' )->willReturn( $db_rows );

		$result = $this->invoke_generate_shipping_settings();

		$this->assertInstanceOf( DBShippingSettingsAdapter::class, $result );

		// The DB rows are consumed unmodified — one service per row, each with the row's own currency.
		$services = $result->getServices();
		$this->assertCount( 2, $services );

		$by_country = [];
		foreach ( $services as $service ) {
			$by_country[ $service->getDeliveryCountry() ] = $service;
		}

		$this->assertArrayHasKey( 'US', $by_country );
		$this->assertArrayHasKey( 'FR', $by_country );
		$this->assertEquals( 'USD', $by_country['US']->getCurrency() );
		$this->assertEquals( 'EUR', $by_country['FR']->getCurrency() );
	}

	private function invoke_generate_shipping_settings() {
		$method = new ReflectionMethod( Settings::class, 'generate_shipping_settings' );
		$method->setAccessible( true );

		return $method->invoke( $this->settings );
	}

	private function all_delivery_times(): array {
		return [
			'US' => [
				'time'     => 1,
				'max_time' => 3,
			],
			'FR' => [
				'time'     => 2,
				'max_time' => 5,
			],
		];
	}
}
