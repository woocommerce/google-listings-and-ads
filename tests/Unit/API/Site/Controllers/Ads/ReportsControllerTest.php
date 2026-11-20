<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\ReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use DateTime;
use Exception;
use ReflectionMethod;
use WP_REST_Request;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class ReportsControllerTest extends UnitTest {

	/** @var ReportsController $controller */
	protected $controller;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new ReportsController( $this->createMock( RESTServer::class ) );

		$container = new Container();
		$container->addShared( WP::class, new WP() );
		$this->controller->set_container( $container );
	}

	/**
	 * Run the shared query argument preparation with the given query params.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	private function prepare( array $params ): array {
		$request = new WP_REST_Request( 'GET', '/wc/gla/ads/reports/campaigns' );
		$request->set_query_params( $params );

		$method = new ReflectionMethod( $this->controller, 'prepare_query_arguments' );
		$method->setAccessible( true );

		return $method->invoke( $this->controller, $request );
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

	public function test_prepare_query_arguments_rejects_an_unparseable_date() {
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Invalid parameter(s): after' );

		$this->prepare( [ 'after' => 'bad' ] );
	}

	public function test_prepare_query_arguments_rejects_an_inverted_date_range() {
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( '`after` must not be later than `before`.' );

		$this->prepare(
			[
				'after'  => '2026-08-07',
				'before' => '2026-08-01',
			]
		);
	}

	public function test_prepare_query_arguments_accepts_a_valid_date_range() {
		$args = $this->prepare(
			[
				'after'  => '2026-08-01',
				'before' => '2026-08-07',
			]
		);

		$this->assertSame( '2026-08-01', $args['after']->format( 'Y-m-d' ) );
		$this->assertSame( '2026-08-07', $args['before']->format( 'Y-m-d' ) );
	}
}
