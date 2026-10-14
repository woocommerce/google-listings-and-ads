<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchAnalyticsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole\ReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole
 */
class ReportsControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_QUERIES = '/wc/gla/search-console/reports/queries';

	/** @var MockObject|Connection */
	protected $connection;

	/** @var MockObject|SearchAnalyticsService */
	protected $search_analytics;

	/** @var ReportsController */
	protected $controller;

	/** @var Container */
	protected $container;

	public function setUp(): void {
		parent::setUp();

		$this->connection       = $this->createMock( Connection::class );
		$this->search_analytics = $this->createMock( SearchAnalyticsService::class );
		$this->controller       = new ReportsController( $this->server, $this->connection, $this->search_analytics );
		$this->container = new Container();
		$this->container->addShared( WP::class, new WP() );
		$this->controller->set_container( $this->container );
		$this->controller->register();
	}

	protected function connect_property( string $property = 'https://example.com/' ): void {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'property' => $property,
				'verified' => SiteVerification::VERIFICATION_STATUS_VERIFIED,
			]
		);
	}

	public function test_queries_report() {
		$this->connect_property();

		$rows = [
			[
				'query'       => 'red shoes',
				'clicks'      => 9,
				'impressions' => 90,
				'ctr'         => 0.1,
				'position'    => 1.2,
			],
		];

		$this->search_analytics->expects( $this->once() )
			->method( 'get_top_queries' )
			->with(
				'https://example.com/',
				$this->callback( fn( DateTimeInterface $after ) => '2026-06-01' === $after->format( 'Y-m-d' ) ),
				$this->callback( fn( DateTimeInterface $before ) => '2026-06-28' === $before->format( 'Y-m-d' ) ),
				100
			)
			->willReturn( $rows );
		$this->search_analytics->expects( $this->once() )->method( 'get_top_queries_limit' )->willReturn( 100 );

		$response = $this->do_request(
			self::ROUTE_QUERIES,
			'GET',
			[
				'after'  => '2026-06-01',
				'before' => '2026-06-28',
			]
		);

		$this->assertEquals(
			[
				'queries' => $rows,
				'limit'   => 100,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_queries_report_requires_a_stored_property() {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'property' => null,
				'verified' => SiteVerification::VERIFICATION_STATUS_UNVERIFIED,
			]
		);
		$this->search_analytics->expects( $this->never() )->method( 'get_top_queries' );

		$response = $this->do_request( self::ROUTE_QUERIES, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_queries_report_requires_a_verified_property() {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'property' => 'https://example.com/',
				'verified' => SiteVerification::VERIFICATION_STATUS_UNVERIFIED,
			]
		);
		$this->search_analytics->expects( $this->never() )->method( 'get_top_queries' );

		$response = $this->do_request( self::ROUTE_QUERIES, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_queries_report_tolerates_stored_data_without_a_verified_flag() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'property' => 'https://example.com/' ] );
		$this->search_analytics->expects( $this->never() )->method( 'get_top_queries' );

		$response = $this->do_request( self::ROUTE_QUERIES, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_queries_report_with_api_error() {
		$this->connect_property();
		$this->search_analytics->method( 'get_top_queries' )
			->willThrowException( new SearchConsoleApiException( 503, [ 'error' => [ 'message' => 'down' ] ], 'method' ) );

		$response = $this->do_request( self::ROUTE_QUERIES, 'GET' );

		$this->assertEquals( 503, $response->get_status() );
	}

	public function test_collection_params_only_expose_the_date_range() {
		$this->assertSame(
			[ 'context', 'after', 'before' ],
			array_keys( $this->controller->get_collection_params() )
		);
	}
}
