<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\ReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use ReflectionMethod;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
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
		$request = new WP_REST_Request( 'GET', '/wc/gla/mc/reports/programs' );
		$request->set_query_params( $params );

		$method = new ReflectionMethod( $this->controller, 'prepare_query_arguments' );
		$method->setAccessible( true );

		return $method->invoke( $this->controller, $request );
	}

	public function test_prepare_query_arguments_rejects_an_unparseable_date() {
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );
		$this->expectExceptionMessage( 'Invalid parameter(s): before' );

		$this->prepare( [ 'before' => 'bad' ] );
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
