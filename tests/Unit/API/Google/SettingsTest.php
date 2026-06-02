<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes protected method for unit testing.
 */
class TestableSettings extends Settings {
	public function get_shipping_rates_collections_from_woocommerce(): array {
		return parent::get_shipping_rates_collections_from_woocommerce();
	}
}

/**
 * Class SettingsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class SettingsTest extends UnitTest {

	/** @var MockObject|TargetAudience */
	protected $target_audience;

	/** @var MockObject|MarketService */
	protected $market_service;

	/** @var MockObject|ShippingZone */
	protected $shipping_zone;

	/** @var Container */
	protected $container;

	/** @var TestableSettings */
	protected $settings;

	public function setUp(): void {
		parent::setUp();

		$this->target_audience = $this->createMock( TargetAudience::class );
		$this->market_service  = $this->createMock( MarketService::class );
		$this->shipping_zone   = $this->createMock( ShippingZone::class );

		$this->container = new Container();
		$this->container->addShared( TargetAudience::class, $this->target_audience );
		$this->container->addShared( MarketService::class, $this->market_service );
		$this->container->addShared( ShippingZone::class, $this->shipping_zone );

		$this->settings = new TestableSettings();
		$this->settings->set_container( $this->container );
	}

	public function test_get_shipping_rates_collections_includes_primary_countries(): void {
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'CA' ] );
		$this->market_service->method( 'get_secondary_market_countries' )->willReturn( [] );
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )->willReturn( [] );

		$result = $this->settings->get_shipping_rates_collections_from_woocommerce();

		$this->assertArrayHasKey( 'US', $result );
		$this->assertArrayHasKey( 'CA', $result );
		$this->assertCount( 2, $result );
	}

	public function test_get_shipping_rates_collections_includes_secondary_market_countries(): void {
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->market_service->method( 'get_secondary_market_countries' )->willReturn( [ 'MU', 'ZW' ] );
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )->willReturn( [] );

		$result = $this->settings->get_shipping_rates_collections_from_woocommerce();

		$this->assertArrayHasKey( 'US', $result );
		$this->assertArrayHasKey( 'MU', $result );
		$this->assertArrayHasKey( 'ZW', $result );
		$this->assertCount( 3, $result );
	}

	public function test_get_shipping_rates_collections_deduplicates_overlapping_countries(): void {
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US', 'MU' ] );
		$this->market_service->method( 'get_secondary_market_countries' )->willReturn( [ 'MU' ] );
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )->willReturn( [] );

		$result = $this->settings->get_shipping_rates_collections_from_woocommerce();

		$this->assertArrayHasKey( 'US', $result );
		$this->assertArrayHasKey( 'MU', $result );
		$this->assertCount( 2, $result );
	}

	public function test_get_shipping_rates_collections_returns_country_rates_collection_instances(): void {
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->market_service->method( 'get_secondary_market_countries' )->willReturn( [ 'MU' ] );
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )->willReturn( [] );

		$result = $this->settings->get_shipping_rates_collections_from_woocommerce();

		foreach ( $result as $collection ) {
			$this->assertInstanceOf( CountryRatesCollection::class, $collection );
		}
	}

	public function test_get_shipping_rates_collections_passes_wc_rates_to_collection(): void {
		$this->target_audience->method( 'get_target_countries' )->willReturn( [ 'US' ] );
		$this->market_service->method( 'get_secondary_market_countries' )->willReturn( [] );

		$rates = [ [ 'method_id' => 'flat_rate', 'cost' => 5 ] ];
		$this->shipping_zone->method( 'get_shipping_rates_for_country' )
			->with( 'US' )
			->willReturn( $rates );

		$result = $this->settings->get_shipping_rates_collections_from_woocommerce();

		$this->assertArrayHasKey( 'US', $result );
		$this->assertInstanceOf( CountryRatesCollection::class, $result['US'] );
	}

	public function test_get_shipping_rates_collections_returns_empty_when_no_countries(): void {
		$this->target_audience->method( 'get_target_countries' )->willReturn( [] );
		$this->market_service->method( 'get_secondary_market_countries' )->willReturn( [] );

		$result = $this->settings->get_shipping_rates_collections_from_woocommerce();

		$this->assertSame( [], $result );
	}
}
