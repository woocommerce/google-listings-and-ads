<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountRegionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\DBShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class SettingsTest extends UnitTest {

	/** @var MockObject|MapiAccountRegionsService */
	protected $regions_service;

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

		$this->regions_service     = $this->createMock( MapiAccountRegionsService::class );
		$this->market_service      = $this->createMock( MarketService::class );
		$this->options             = $this->createMock( OptionsInterface::class );
		$this->wc_proxy            = $this->createMock( WC::class );
		$this->target_audience     = $this->createMock( TargetAudience::class );
		$this->shipping_time_query = $this->createMock( ShippingTimeQuery::class );
		$this->shipping_rate_query = $this->createMock( ShippingRateQuery::class );
		$this->shipping_zone       = $this->createMock( ShippingZone::class );

		$this->container = new Container();
		$this->container->addShared( MapiAccountRegionsService::class, $this->regions_service );
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

	/**
	 * Invoke the protected sync_shipping_regions().
	 *
	 * @param array $regions
	 */
	protected function sync_regions( array $regions ): void {
		$method = new ReflectionMethod( Settings::class, 'sync_shipping_regions' );
		$method->setAccessible( true );
		$method->invoke( $this->settings, $regions );
	}

	public function test_sync_shipping_regions_noop_when_empty() {
		$this->regions_service->expects( $this->never() )->method( 'insert_region' );
		$this->regions_service->expects( $this->never() )->method( 'update_region' );

		$this->sync_regions( [] );
	}

	public function test_sync_shipping_regions_inserts_new_regions() {
		$region = [ 'displayName' => '90210' ];

		$this->regions_service->expects( $this->once() )
			->method( 'insert_region' )
			->with( '90210', $region );
		$this->regions_service->expects( $this->never() )
			->method( 'update_region' );

		$this->sync_regions( [ '90210' => $region ] );
	}

	public function test_sync_shipping_regions_updates_existing_region_on_400() {
		$region = [ 'displayName' => '90210' ];

		$this->regions_service->expects( $this->once() )
			->method( 'insert_region' )
			->willThrowException( new MerchantApiException( 400, [], __METHOD__ ) );
		$this->regions_service->expects( $this->once() )
			->method( 'update_region' )
			->with( '90210', $region, 'displayName,postalCodeArea' );

		$this->sync_regions( [ '90210' => $region ] );
	}

	public function test_sync_shipping_regions_rethrows_non_400() {
		$this->regions_service->expects( $this->once() )
			->method( 'insert_region' )
			->willThrowException( new MerchantApiException( 401, [], __METHOD__ ) );
		$this->regions_service->expects( $this->never() )
			->method( 'update_region' );

		$this->expectException( MerchantApiException::class );
		$this->sync_regions( [ '90210' => [ 'displayName' => '90210' ] ] );
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
		foreach ( $adapter->get_services() as $service ) {
			$by_country[ $service['deliveryCountries'][0] ] = $service;
		}

		$this->assertSame( 'USD', $by_country['US']['currencyCode'] );
		$this->assertSame( 'EUR', $by_country['FR']['currencyCode'] );
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

	public function test_generate_shipping_settings_prefers_per_row_currency_over_country_map(): void {
		// MarketService says FR is USD, but the DB row for FR carries EUR. The
		// per-row currency must win so secondary-market rates aren't silently
		// rewritten to the primary store currency.
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
					'currency'      => [ 'USD' ],
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
					'country'  => 'US',
					'currency' => 'USD',
					'rate'     => 10,
					'options'  => [],
				],
				[
					'country'  => 'FR',
					'currency' => 'EUR',
					'rate'     => 5,
					'options'  => [],
				],
			]
		);

		$adapter = $this->invoke( 'generate_shipping_settings' );

		$this->assertInstanceOf( DBShippingSettingsAdapter::class, $adapter );

		$by_country = [];
		foreach ( $adapter->get_services() as $service ) {
			$by_country[ $service['deliveryCountries'][0] ] = $service;
		}

		$this->assertSame( 'USD', $by_country['US']['currencyCode'] );
		$this->assertSame( 'EUR', $by_country['FR']['currencyCode'] );
	}

	public function test_generate_shipping_settings_routes_automatic_mode_through_wc_adapter(): void {
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'country'       => 'US',
					'currency'      => [ 'USD' ],
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->options->method( 'get' )->willReturnCallback(
			function ( $key, $fallback = null ) {
				switch ( $key ) {
					case OptionsInterface::MERCHANT_CENTER:
						return [ 'shipping_rate' => 'automatic' ];
					case OptionsInterface::TARGET_AUDIENCE:
						return [ 'countries' => [ 'US' ] ];
					case OptionsInterface::MERCHANT_ID:
						return 1234567890;
					default:
						return $fallback;
				}
			}
		);

		$this->wc_proxy->method( 'get_woocommerce_currency' )->willReturn( 'USD' );
		$this->market_service->method( 'get_shipping_sync_countries' )->willReturn( [ 'US' ] );
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )->willReturn( [] );
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'US' => [
					'time'     => 1,
					'max_time' => 3,
				],
			]
		);
		// Automatic mode is gated, so the DB rate query must never be touched.
		$this->shipping_rate_query->expects( $this->never() )->method( 'get_results' );

		$adapter = $this->invoke( 'generate_shipping_settings' );

		$this->assertInstanceOf( WCShippingSettingsAdapter::class, $adapter );
		$this->assertNotInstanceOf( DBShippingSettingsAdapter::class, $adapter );
	}

	public function test_automatic_mode_builds_services_for_secondary_market_countries(): void {
		$this->market_service->method( 'get_markets' )->willReturn(
			[
				'primary' => [
					'country'       => null,
					'currency'      => [ 'USD' ],
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
				'fr'      => [
					'country'       => 'FR',
					'currency'      => [ 'EUR' ],
					'feed_label'    => 'FR',
					'shipping_rate' => 'automatic',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->options->method( 'get' )->willReturnCallback(
			function ( $key, $fallback = null ) {
				switch ( $key ) {
					case OptionsInterface::MERCHANT_CENTER:
						return [ 'shipping_rate' => 'automatic' ];
					case OptionsInterface::MERCHANT_ID:
						return 1234567890;
					default:
						return $fallback;
				}
			}
		);

		$this->wc_proxy->method( 'get_woocommerce_currency' )->willReturn( 'USD' );
		// France's country is removed from the target audience when the market
		// is added, so the country list must come from the market-aware method.
		$this->market_service->method( 'get_shipping_sync_countries' )->willReturn( [ 'US', 'FR' ] );
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )->willReturnCallback(
			static function ( string $country ) {
				return [
					new LocationRate( new ShippingLocation( 1, $country ), new ShippingRate( 10 ) ),
				];
			}
		);
		$this->shipping_time_query->method( 'get_all_shipping_times' )->willReturn(
			[
				'US' => [
					'time'     => 1,
					'max_time' => 3,
				],
				'FR' => [
					'time'     => 0,
					'max_time' => 2,
				],
			]
		);

		$adapter = $this->invoke( 'generate_shipping_settings' );

		$countries = array_map(
			static function ( array $service ) {
				return $service['deliveryCountries'][0];
			},
			$adapter->get_services()
		);

		$this->assertContains( 'US', $countries );
		$this->assertContains( 'FR', $countries );

		$by_country = [];
		foreach ( $adapter->get_services() as $service ) {
			$by_country[ $service['deliveryCountries'][0] ] = $service;
		}

		// The France service carries the market's own currency.
		$this->assertSame( 'EUR', $by_country['FR']['currencyCode'] );
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
