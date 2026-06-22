<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\DBShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class SettingsTest extends UnitTest {

	/** @var MockObject|MarketService */
	protected $market_service;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var MockObject|WC */
	protected $wc_proxy;

	/** @var MockObject|TargetAudience */
	protected $target_audience;

	/** @var MockObject|ShippingTimeQuery */
	protected $shipping_time_query;

	/** @var MockObject|ShippingRateQuery */
	protected $shipping_rate_query;

	/** @var MockObject|ShippingZone */
	protected $shipping_zone;

	/** @var Container */
	protected $container;

	/** @var Settings */
	protected $settings;

	public function setUp(): void {
		parent::setUp();

		$this->market_service      = $this->createMock( MarketService::class );
		$this->options             = $this->createMock( OptionsInterface::class );
		$this->wc_proxy            = $this->createMock( WC::class );
		$this->target_audience     = $this->createMock( TargetAudience::class );
		$this->shipping_time_query = $this->createMock( ShippingTimeQuery::class );
		$this->shipping_rate_query = $this->createMock( ShippingRateQuery::class );
		$this->shipping_zone       = $this->createMock( ShippingZone::class );

		$this->container = new Container();
		$this->container->addShared( MarketService::class, $this->market_service );
		$this->container->addShared( OptionsInterface::class, $this->options );
		$this->container->addShared( WC::class, $this->wc_proxy );
		$this->container->addShared( TargetAudience::class, $this->target_audience );
		$this->container->addShared( ShippingTimeQuery::class, $this->shipping_time_query );
		$this->container->addShared( ShippingRateQuery::class, $this->shipping_rate_query );
		$this->container->addShared( ShippingZone::class, $this->shipping_zone );

		$this->settings = new Settings();
		$this->settings->set_container( $this->container );
	}

	public function test_should_sync_shipping_returns_true_when_market_service_has_syncable_markets(): void {
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->assertTrue( $this->invoke( 'should_sync_shipping' ) );
	}

	public function test_should_sync_shipping_returns_false_when_market_service_has_no_syncable_markets(): void {
		$this->market_service->method( 'has_syncable_markets' )->willReturn( false );

		$this->assertFalse( $this->invoke( 'should_sync_shipping' ) );
	}

	public function test_generate_shipping_settings_produces_db_adapter_with_per_country_currency(): void {
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'country'       => 'US',
					'currency'      => [ 'USD' ],
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				'fr'      => [
					'country'       => 'FR',
					'currency'      => [ 'EUR' ],
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->options->method( 'get' )->willReturnCallback(
			function ( $key, $fallback = null ) {
				switch ( $key ) {
					case OptionsInterface::MERCHANT_CENTER:
						return [ 'shipping_rate' => 'flat' ];
					case OptionsInterface::MERCHANT_ID:
						return 1234567890;
					default:
						return $fallback;
				}
			}
		);
		$this->wc_proxy->method( 'get_woocommerce_currency' )->willReturn( 'USD' );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'US' => [
					'time'     => 1,
					'max_time' => 2,
				],
				'FR' => [
					'time'     => 2,
					'max_time' => 4,
				],
			]
		);
		$this->shipping_rate_query->method( 'get_results' )->willReturn(
			[
				[
					'country' => 'US',
					'rate'    => 10,
					'options' => [],
				],
				[
					'country' => 'FR',
					'rate'    => 5,
					'options' => [],
				],
			]
		);

		$adapter = $this->invoke( 'generate_shipping_settings' );

		$this->assertInstanceOf( DBShippingSettingsAdapter::class, $adapter );

		$by_country = [];
		foreach ( $adapter->getServices() as $service ) {
			$by_country[ $service->getDeliveryCountry() ] = $service;
		}

		$this->assertSame( 'USD', $by_country['US']->getCurrency() );
		$this->assertSame( 'EUR', $by_country['FR']->getCurrency() );
	}

	public function test_generate_shipping_settings_skips_manual_markets_from_currency_map(): void {
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'country'       => 'US',
					'currency'      => [ 'USD' ],
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
				'fr'      => [
					'country'       => 'FR',
					'currency'      => [ 'EUR' ],
					'shipping_rate' => 'manual',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->wc_proxy->method( 'get_woocommerce_currency' )->willReturn( 'USD' );

		$map = $this->invoke( 'build_country_currency_map' );

		$this->assertSame( [ 'US' => 'USD' ], $map );
	}

	/**
	 * Invokes a protected method on the Settings instance via reflection so we
	 * can test the multi-market gates without exposing internals.
	 *
	 * @param string $method
	 * @return mixed
	 */
	private function invoke( string $method ) {
		$ref = ( new ReflectionClass( Settings::class ) )->getMethod( $method );
		$ref->setAccessible( true );
		return $ref->invoke( $this->settings );
	}
}
