<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\ReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class ReportsControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_PROGRAMS = '/wc/gla/ads/reports/programs';
	protected const ROUTE_PRODUCTS = '/wc/gla/ads/reports/products';

	/** @var ReportsController $controller */
	protected $controller;

	/** @var MockObject|AdsReport $ads_report */
	protected $ads_report;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_report = $this->createMock( AdsReport::class );

		$wp = $this->createMock( WP::class );
		$wp->method( 'wp_timezone' )->willReturn( new DateTimeZone( 'UTC' ) );

		$container = new Container();
		$container->addShared( AdsReport::class, $this->ads_report );
		$container->addShared( WP::class, $wp );

		$this->controller = new ReportsController( $this->server );
		$this->controller->set_container( $container );
		$this->controller->register();
	}

	/**
	 * Run the date-range validation with the given prepared args.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|null
	 */
	private function validate( array $args ) {
		return $this->controller->validate_product_day_interval_range( $args );
	}

	public function test_rejects_day_interval_range_over_the_max() {
		$result = $this->validate(
			[
				'interval' => 'day',
				'after'    => new DateTime( '2024-01-01' ),
				'before'   => new DateTime( '2025-06-01' ),
			]
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'gla_report_date_range_too_large', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_allows_day_interval_range_within_the_max() {
		$result = $this->validate(
			[
				'interval' => 'day',
				'after'    => new DateTime( '2024-01-01' ),
				'before'   => new DateTime( '2024-03-01' ),
			]
		);

		$this->assertNull( $result );
	}

	public function test_ignores_non_day_intervals_regardless_of_span() {
		$result = $this->validate(
			[
				'interval' => 'month',
				'after'    => new DateTime( '2020-01-01' ),
				'before'   => new DateTime( '2025-01-01' ),
			]
		);

		$this->assertNull( $result );
	}

	public function test_ignores_args_without_datetime_range() {
		$this->assertNull( $this->validate( [ 'interval' => 'day' ] ) );
	}

	public function test_schema_includes_truncated() {
		$properties = $this->controller->get_item_schema()['properties'];

		$this->assertArrayHasKey( 'truncated', $properties );
		$this->assertSame( 'boolean', $properties['truncated']['type'] );
	}

	public function test_programs_report_passes_truncated_through() {
		$this->ads_report->expects( $this->once() )
			->method( 'get_report_data' )
			->with( 'campaigns', $this->anything() )
			->willReturn(
				[
					'campaigns' => [],
					'totals'    => [ 'clicks' => 10 ],
					'truncated' => true,
				]
			);

		$response = $this->do_request( self::ROUTE_PROGRAMS, 'GET', [ 'fields' => [ 'clicks' ] ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['truncated'] );
		$this->assertSame( [ 'clicks' => 10 ], $response->get_data()['totals'] );
	}

	public function test_products_report_defaults_truncated_to_false() {
		$this->ads_report->expects( $this->once() )
			->method( 'get_report_data' )
			->with( 'products', $this->anything() )
			->willReturn(
				[
					'products' => [],
					'totals'   => [ 'clicks' => 10 ],
				]
			);

		$response = $this->do_request( self::ROUTE_PRODUCTS, 'GET', [ 'fields' => [ 'clicks' ] ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['truncated'] );
	}
}
