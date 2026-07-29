<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountRegionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;
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

	/** @var Settings */
	protected $settings;

	public function setUp(): void {
		parent::setUp();

		$this->regions_service = $this->createMock( MapiAccountRegionsService::class );

		$container = new Container();
		$container->addShared( MapiAccountRegionsService::class, $this->regions_service );

		$this->settings = new Settings();
		$this->settings->set_container( $container );
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
}
