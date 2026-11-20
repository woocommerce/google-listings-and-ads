<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\ReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
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
}
