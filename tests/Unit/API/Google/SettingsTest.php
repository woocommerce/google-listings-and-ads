<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountRegionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
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

	/** @var MockObject|WPML */
	protected $wpml;

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

		// Amounts convert one-to-one so currency assertions stay readable;
		// tests exercising real conversion arithmetic live in the adapter tests.
		$this->wpml = $this->createMock( WPML::class );
		$this->wpml->method( 'convert_amount' )->willReturnCallback(
			static function ( float $amount ): float {
				return $amount;
			}
		);

		// Mirrors MarketService::get_participating_currencies() with every
		// configured currency treated as convertible.
		$this->market_service->method( 'get_participating_currencies' )->willReturnCallback(
			static function ( array $market ): array {
				$configured = is_array( $market['currency'] ?? null ) ? $market['currency'] : [];

				return array_values( array_unique( array_filter( array_map( 'strval', $configured ) ) ) );
			}
		);

		$this->container = new Container();
		$this->container->addShared( MapiAccountRegionsService::class, $this->regions_service );
		$this->container->addShared( MarketService::class, $this->market_service );
		$this->container->addShared( OptionsInterface::class, $this->options );
		$this->container->addShared( WC::class, $this->wc_proxy );
		$this->container->addShared( TargetAudience::class, $this->target_audience );
		$this->container->addShared( ShippingTimeQuery::class, $this->shipping_time_query );
		$this->container->addShared( ShippingRateQuery::class, $this->shipping_rate_query );
		$this->container->addShared( ShippingZone::class, $this->shipping_zone );
		$this->container->addShared( WPML::class, $this->wpml );

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
		$this->market_service->method( 'get_participating_markets' )->willReturn(
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

	/**
	 * The shipping method is global, so MarketService::get_markets() returns the same
	 * method for every market (GOOWOO-773) — a mix of `manual` and non-`manual` markets
	 * is no longer representable. When the global method is `manual`, every market is
	 * skipped and the currency map is empty. build_country_currency_map keeps the guard
	 * defensively so a stray manual market can never leak into the synced services.
	 */
	public function test_generate_shipping_settings_skips_manual_markets_from_currency_map(): void {
		$this->market_service->method( 'get_primary_market' )->willReturn(
			[
				'countries'     => [ 'US' ],
				'country'       => null,
				'currency'      => [ 'USD' ],
				'shipping_rate' => 'flat',
			]
		);
		$this->market_service->method( 'get_participating_markets' )->willReturn(
			[
				'primary' => [
					'country'       => null,
					'currency'      => [ 'USD' ],
					'shipping_rate' => 'manual',
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

		$this->assertSame( [ 'US' => [ 'USD' ] ], $map );
	}

	public function test_exchange_rate_map_contains_secondary_markets_with_a_positive_rate(): void {
		$this->market_service->method( 'get_participating_markets' )->willReturn(
			[
				'primary' => [
					'country'       => 'US',
					'currency'      => [ 'USD' ],
					'shipping_rate' => 'flat',
				],
				'fr'      => [
					'country'       => 'FR',
					'currency'      => [ 'EUR' ],
					'exchange_rate' => '0.9',
					'shipping_rate' => 'flat',
				],
			]
		);

		// The primary market never carries a rate, so only the secondary one is mapped.
		$this->assertSame( [ 'FR' => 0.9 ], $this->invoke( 'build_country_exchange_rate_map' ) );
	}

	/**
	 * Markets that cannot use a rate are left out: manual shipping gets no Merchant Center
	 * service at all, and a missing, zero, negative or non-numeric rate is not a rate.
	 */
	public function test_exchange_rate_map_skips_manual_and_non_positive_rates(): void {
		$this->market_service->method( 'get_participating_markets' )->willReturn(
			[
				'manual'      => [
					'country'       => 'DE',
					'exchange_rate' => '1.1',
					'shipping_rate' => 'manual',
				],
				'zero'        => [
					'country'       => 'ES',
					'exchange_rate' => '0',
					'shipping_rate' => 'flat',
				],
				'negative'    => [
					'country'       => 'IT',
					'exchange_rate' => '-1',
					'shipping_rate' => 'flat',
				],
				'not_numeric' => [
					'country'       => 'NL',
					'exchange_rate' => 'abc',
					'shipping_rate' => 'flat',
				],
				'absent'      => [
					'country'       => 'PT',
					'shipping_rate' => 'flat',
				],
				'no_country'  => [
					'country'       => null,
					'exchange_rate' => '2.0',
					'shipping_rate' => 'flat',
				],
			]
		);

		$this->assertSame( [], $this->invoke( 'build_country_exchange_rate_map' ) );
	}

	/**
	 * With a non-manual global method every market contributes to the currency map,
	 * each with its own currency.
	 */
	public function test_generate_shipping_settings_includes_every_market_in_currency_map(): void {
		$this->market_service->method( 'get_primary_market' )->willReturn(
			[
				'countries'     => [ 'US' ],
				'country'       => null,
				'currency'      => [ 'USD' ],
				'shipping_rate' => 'flat',
			]
		);
		$this->market_service->method( 'get_participating_markets' )->willReturn(
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

		$this->wc_proxy->method( 'get_woocommerce_currency' )->willReturn( 'USD' );

		$map = $this->invoke( 'build_country_currency_map' );

		$this->assertSame(
			[
				'US' => [ 'USD' ],
				'FR' => [ 'EUR' ],
			],
			$map
		);
	}

	public function test_country_currency_map_includes_primary_countries_with_extra_currencies(): void {
		$this->market_service->method( 'get_primary_market' )->willReturn(
			[
				'countries'     => [ 'US', 'GB' ],
				'country'       => null,
				'currency'      => [ 'USD', 'EUR' ],
				'shipping_rate' => 'flat',
			]
		);
		$this->market_service->method( 'get_participating_markets' )->willReturn( [] );

		$this->wc_proxy->method( 'get_woocommerce_currency' )->willReturn( 'USD' );

		$map = $this->invoke( 'build_country_currency_map' );

		$this->assertSame(
			[
				'US' => [ 'USD', 'EUR' ],
				'GB' => [ 'USD', 'EUR' ],
			],
			$map
		);
	}

	public function test_country_currency_map_lists_every_participating_currency_of_a_market(): void {
		$this->market_service->method( 'get_participating_markets' )->willReturn(
			[
				'ae' => [
					'country'       => 'AE',
					'currency'      => [ 'USD', 'AED' ],
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
			]
		);

		$this->wc_proxy->method( 'get_woocommerce_currency' )->willReturn( 'USD' );

		$map = $this->invoke( 'build_country_currency_map' );

		$this->assertSame( [ 'AE' => [ 'USD', 'AED' ] ], $map );
	}

	public function test_flat_mode_leaves_out_service_when_row_currency_conflicts_with_market_currencies(): void {
		// MarketService says FR sells in USD, but the DB row for FR carries EUR
		// amounts. There is no conversion path between the two, so no FR
		// service is synced (a mislabelled or mismatched service would cause
		// disapprovals) and the conflict is reported.
		$reported = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$reported ) {
				$reported[] = $message;
			}
		);

		$this->market_service->method( 'get_participating_markets' )->willReturn(
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

		$countries = array_map(
			static function ( array $service ): string {
				return $service['deliveryCountries'][0];
			},
			$adapter->get_services()
		);

		$this->assertSame( [ 'US' ], $countries );
		$this->assertCount( 1, $reported );
		$this->assertStringContainsString( 'FR', $reported[0] );
	}

	public function test_generate_shipping_settings_routes_automatic_mode_through_wc_adapter(): void {
		$this->market_service->method( 'get_participating_markets' )->willReturn(
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
		$this->market_service->method( 'get_participating_markets' )->willReturn(
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

	public function test_flat_mode_excludes_rate_rows_for_excluded_market_countries(): void {
		$this->market_service->method( 'get_participating_markets' )->willReturn(
			[
				'primary' => [
					'country'       => null,
					'currency'      => [ 'USD' ],
					'shipping_rate' => 'flat',
					'shipping_time' => 'flat',
				],
			]
		);
		// France's market is excluded (non-store currency, conversion unavailable),
		// so its rate row must not produce a Merchant Center shipping service.
		$this->market_service->method( 'get_excluded_market_countries' )->willReturn( [ 'FR' ] );

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

		$countries = array_map(
			static function ( $service ) {
				return $service['deliveryCountries'][0];
			},
			$adapter->get_services()
		);

		$this->assertSame( [ 'US' ], $countries );
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
