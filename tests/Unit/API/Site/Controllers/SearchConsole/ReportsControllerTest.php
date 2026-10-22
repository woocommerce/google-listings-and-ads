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

	protected const ROUTE_QUERIES  = '/wc/gla/search-console/reports/queries';
	protected const ROUTE_OVERVIEW = '/wc/gla/search-console/reports/overview';

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

		$this->connection       = $this->getMockBuilder( Connection::class )->disableOriginalConstructor()->onlyMethods( [ 'get_connection_data' ] )->getMock();
		$this->search_analytics = $this->createMock( SearchAnalyticsService::class );
		$this->controller       = new ReportsController( $this->server, $this->connection, $this->search_analytics );
		$this->container        = new Container();
		$this->container->addShared( WP::class, new WP() );
		$this->controller->set_container( $this->container );
		$this->controller->register();
	}

	protected function connect_property( string $property = 'https://example.com/' ): void {
		$this->connection->method( 'get_connection_data' )->willReturn(
			[
				'property' => $property,
				'verified' => SiteVerification::VERIFICATION_STATUS_VERIFIED,
				'state'    => Connection::STATE_CONNECTED,
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

		// The overview route's properties are declared in the same shared schema (see
		// get_schema_properties()), so prepare_item_for_response() null-pads them here too.
		$this->assertEquals(
			[
				'queries'   => $rows,
				'limit'     => 100,
				'totals'    => null,
				'intervals' => null,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_queries_report_requires_a_connected_property() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'property' => null ] );
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

	public function test_schema_describes_the_report() {
		$schema = $this->controller->get_item_schema();

		$this->assertSame( 'search_console_reports', $schema['title'] );
		$this->assertSame(
			[ 'queries', 'limit', 'totals', 'intervals' ],
			array_keys( $schema['properties'] )
		);
		$this->assertSame( 'array', $schema['properties']['queries']['type'] );
		$this->assertSame( 'integer', $schema['properties']['limit']['type'] );
		$this->assertSame(
			[
				'query'       => 'string',
				'clicks'      => 'integer',
				'impressions' => 'integer',
				'ctr'         => [ 'number', 'null' ],
				'position'    => [ 'number', 'null' ],
			],
			array_map(
				fn( array $property ) => $property['type'],
				$schema['properties']['queries']['items']['properties']
			)
		);
		$this->assertSame( 'object', $schema['properties']['totals']['type'] );
		$this->assertSame(
			[
				'clicks'      => 'integer',
				'impressions' => 'integer',
				'ctr'         => [ 'number', 'null' ],
				'position'    => [ 'number', 'null' ],
			],
			array_map(
				fn( array $property ) => $property['type'],
				$schema['properties']['totals']['properties']
			)
		);
		$this->assertSame( 'array', $schema['properties']['intervals']['type'] );
		$this->assertSame(
			[ 'interval', 'subtotals' ],
			array_keys( $schema['properties']['intervals']['items']['properties'] )
		);
		$this->assertNotEmpty( $schema['properties']['intervals']['items']['properties']['subtotals']['description'] );
		$this->assertSame(
			[ 'view' ],
			$schema['properties']['intervals']['items']['properties']['subtotals']['context']
		);
	}

	public function test_collection_params_expose_only_the_date_range() {
		$this->assertSame(
			[ 'context', 'after', 'before' ],
			array_keys( $this->controller->get_collection_params() )
		);
	}

	public function test_queries_report_ignores_an_unrecognized_interval() {
		// interval is scoped to the overview route alone (see get_overview_collection_params()).
		// /queries has never read it; this confirms that stays true even now that /overview does.
		$this->connect_property();
		$this->search_analytics->method( 'get_top_queries_limit' )->willReturn( 100 );

		$response = $this->do_request(
			self::ROUTE_QUERIES,
			'GET',
			[
				'after'    => '2026-06-01',
				'before'   => '2026-06-28',
				'interval' => 'week',
			]
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_overview_report_rejects_an_interval_other_than_day() {
		$this->connect_property();
		$this->search_analytics->expects( $this->never() )->method( 'get_overview' );

		$response = $this->do_request(
			self::ROUTE_OVERVIEW,
			'GET',
			[
				'after'    => '2026-06-01',
				'before'   => '2026-06-28',
				'interval' => 'week',
			]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_overview_report() {
		$this->connect_property();

		$this->search_analytics->expects( $this->once() )
			->method( 'get_overview' )
			->with(
				'https://example.com/',
				$this->callback( fn( DateTimeInterface $after ) => '2026-06-01' === $after->format( 'Y-m-d' ) ),
				$this->callback( fn( DateTimeInterface $before ) => '2026-06-28' === $before->format( 'Y-m-d' ) )
			)
			->willReturn(
				[
					'totals'    => [
						'clicks'      => 42,
						'impressions' => 420,
						'ctr'         => 0.1,
						'position'    => 2.5,
					],
					'intervals' => [
						[
							'interval'  => '2026-06-01',
							'subtotals' => [
								'clicks'      => 9,
								'impressions' => 90,
								'ctr'         => 0.1,
								'position'    => 1.2,
							],
						],
					],
				]
			);

		$response = $this->do_request(
			self::ROUTE_OVERVIEW,
			'GET',
			[
				'after'  => '2026-06-01',
				'before' => '2026-06-28',
			]
		);

		// The queries route's properties are declared in the same shared schema (see
		// get_schema_properties()), so prepare_item_for_response() null-pads them here too.
		$this->assertEquals(
			[
				'queries'   => null,
				'limit'     => null,
				'totals'    => [
					'clicks'      => 42,
					'impressions' => 420,
					'ctr'         => 0.1,
					'position'    => 2.5,
				],
				'intervals' => [
					[
						'interval'  => '2026-06-01',
						'subtotals' => [
							'clicks'      => 9,
							'impressions' => 90,
							'ctr'         => 0.1,
							'position'    => 1.2,
						],
					],
				],
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_overview_report_requires_a_connected_property() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'property' => null ] );
		$this->search_analytics->expects( $this->never() )->method( 'get_overview' );

		$response = $this->do_request( self::ROUTE_OVERVIEW, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_overview_report_with_api_error() {
		$this->connect_property();
		$this->search_analytics->method( 'get_overview' )
			->willThrowException( new SearchConsoleApiException( 503, [ 'error' => [ 'message' => 'down' ] ], 'method' ) );

		$response = $this->do_request( self::ROUTE_OVERVIEW, 'GET' );

		$this->assertEquals( 503, $response->get_status() );
	}
}
