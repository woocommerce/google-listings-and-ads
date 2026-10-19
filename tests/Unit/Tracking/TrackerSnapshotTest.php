<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TrackerSnapshot;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TrackerSnapshotTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking
 */
class TrackerSnapshotTest extends UnitTest {

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var TrackerSnapshot $snapshot */
	protected $snapshot;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->location_service = $this->createMock( LocationService::class );

		$market_service = $this->createMock( MarketService::class );
		$market_service->method( 'get_non_default_locale_market_counts' )->willReturn(
			[
				'multilingual'  => 0,
				'multicurrency' => 0,
			]
		);

		$container = $this->createMock( Container::class );
		$container->method( 'get' )->willReturnCallback(
			function ( string $id ) use ( $market_service ) {
				if ( LocationService::class === $id ) {
					return $this->location_service;
				}

				if ( MarketService::class === $id ) {
					return $market_service;
				}

				return $this->createMock( $id );
			}
		);

		$this->snapshot = new TrackerSnapshot();
		$this->snapshot->set_container( $container );
		$this->snapshot->set_options_object( $this->createMock( OptionsInterface::class ) );
		$this->snapshot->register();
	}

	public function test_reports_a_connected_business_profile_location() {
		$this->location_service->method( 'get_selected_location' )->willReturn( [ 'id' => '100' ] );

		$this->assertSame( 'yes', $this->get_settings()['google_business_profile_connected'] );
	}

	public function test_reports_no_business_profile_connection_when_no_location_is_connected() {
		$this->location_service->method( 'get_selected_location' )->willReturn( null );

		$this->assertSame( 'no', $this->get_settings()['google_business_profile_connected'] );
	}

	/**
	 * Get the settings this plugin adds to the WC Tracker snapshot.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		return apply_filters( 'woocommerce_tracker_data', [] )['extensions']['gla']['settings'];
	}
}
