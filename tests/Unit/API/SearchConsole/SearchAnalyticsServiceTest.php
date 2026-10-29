<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchAnalyticsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchAnalyticsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class SearchAnalyticsServiceTest extends UnitTest {

	protected const FILTER = 'woocommerce_gla_search_console_top_queries_limit';

	/** @var MockObject|SearchConsoleApiClient */
	protected $client;

	/** @var MockObject|OptionsInterface */
	protected $options;

	/** @var SearchAnalyticsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( SearchConsoleApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->service = new SearchAnalyticsService( $this->client );
		$this->service->set_options_object( $this->options );
	}

	public function tearDown(): void {
		remove_all_filters( self::FILTER );

		parent::tearDown();
	}

	protected function get_top_queries( string $property = 'https://example.com/' ): array {
		return $this->service->get_top_queries( $property, new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );
	}

	public function test_requests_the_query_dimension_for_the_period_with_the_default_limit() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				'sites/https%3A%2F%2Fexample.com%2F/searchAnalytics/query',
				[
					'startDate'  => '2026-06-01',
					'endDate'    => '2026-06-28',
					'dimensions' => [ 'query' ],
					'rowLimit'   => 100,
				]
			)
			->willReturn( [] );

		$this->assertSame( [], $this->get_top_queries() );
	}

	public function test_maps_rows_and_sorts_them_by_clicks_descending() {
		$this->client->method( 'post' )->willReturn(
			[
				'rows' => [
					[
						'keys'        => [ 'blue shoes' ],
						'clicks'      => 4,
						'impressions' => 40,
						'ctr'         => 0.1,
						'position'    => 3.5,
					],
					[
						'keys'        => [ 'red shoes' ],
						'clicks'      => 9,
						'impressions' => 90,
						'ctr'         => 0.1,
						'position'    => 1.2,
					],
				],
			]
		);

		$this->assertSame(
			[
				[
					'query'       => 'red shoes',
					'clicks'      => 9,
					'impressions' => 90,
					'ctr'         => 0.1,
					'position'    => 1.2,
				],
				[
					'query'       => 'blue shoes',
					'clicks'      => 4,
					'impressions' => 40,
					'ctr'         => 0.1,
					'position'    => 3.5,
				],
			],
			$this->get_top_queries()
		);
	}

	public function test_maps_a_query_with_no_impressions_to_null_ctr_and_position() {
		$this->client->method( 'post' )->willReturn(
			[
				'rows' => [
					[
						'keys'        => [ 'rare term' ],
						'clicks'      => 0,
						'impressions' => 0,
					],
				],
			]
		);

		$this->assertSame(
			[
				[
					'query'       => 'rare term',
					'clicks'      => 0,
					'impressions' => 0,
					'ctr'         => null,
					'position'    => null,
				],
			],
			$this->get_top_queries()
		);
	}

	public function test_caps_the_rows_at_the_limit_even_if_the_api_returns_more() {
		add_filter( self::FILTER, fn() => 2 );

		$rows = [];
		foreach ( [ 'a', 'b', 'c' ] as $i => $keyword ) {
			$rows[] = [
				'keys'   => [ $keyword ],
				'clicks' => $i + 1,
			];
		}
		$this->client->method( 'post' )->willReturn( [ 'rows' => $rows ] );

		$result = $this->get_top_queries();

		$this->assertSame( [ 'c', 'b' ], array_column( $result, 'query' ) );
	}

	public function test_uses_an_explicit_limit_without_reading_the_filter() {
		add_filter( self::FILTER, fn() => 500 );

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				$this->anything(),
				$this->callback( fn( array $body ) => 7 === $body['rowLimit'] )
			)
			->willReturn( [] );

		$this->service->get_top_queries( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ), 7 );
	}

	/**
	 * @dataProvider provide_explicit_limits
	 *
	 * @param int $explicit Limit passed by the caller.
	 * @param int $expected Row limit sent to the API.
	 */
	public function test_clamps_an_explicit_limit( int $explicit, int $expected ) {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				$this->anything(),
				$this->callback( fn( array $body ) => $expected === $body['rowLimit'] )
			)
			->willReturn( [] );

		$this->service->get_top_queries( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ), $explicit );
	}

	public function provide_explicit_limits(): array {
		return [
			'zero is raised to one'        => [ 0, 1 ],
			'above the API max is clamped' => [ 50000, 25000 ],
			'in range is kept'             => [ 300, 300 ],
		];
	}

	public function test_default_limit_is_100() {
		$this->assertSame( 100, $this->service->get_top_queries_limit() );
	}

	public function test_limit_can_be_raised_with_the_filter() {
		add_filter( self::FILTER, fn() => 500 );

		$this->assertSame( 500, $this->service->get_top_queries_limit() );
	}

	/**
	 * @dataProvider provide_invalid_limits
	 *
	 * @param mixed $filtered_value Value returned by the filter callback.
	 * @param int   $expected       Expected effective limit.
	 */
	public function test_limit_is_coerced_to_a_usable_value( $filtered_value, int $expected ) {
		add_filter( self::FILTER, fn() => $filtered_value );

		$this->assertSame( $expected, $this->service->get_top_queries_limit() );
	}

	public function provide_invalid_limits(): array {
		return [
			'non-numeric falls back to default' => [ 'many', 100 ],
			'null falls back to default'        => [ null, 100 ],
			'zero is raised to one'             => [ 0, 1 ],
			'negative is raised to one'         => [ -5, 1 ],
			'above the API max is clamped'      => [ 1000000, 25000 ],
			'numeric string is accepted'        => [ '250', 250 ],
		];
	}

	public function test_overview_requests_totals_and_daily_intervals_for_the_period() {
		$this->client->expects( $this->exactly( 2 ) )
			->method( 'post' )
			->withConsecutive(
				[
					'sites/https%3A%2F%2Fexample.com%2F/searchAnalytics/query',
					[
						'startDate' => '2026-06-01',
						'endDate'   => '2026-06-28',
					],
				],
				[
					'sites/https%3A%2F%2Fexample.com%2F/searchAnalytics/query',
					[
						'startDate'  => '2026-06-01',
						'endDate'    => '2026-06-28',
						'dimensions' => [ 'date' ],
					],
				]
			)
			->willReturn( [] );

		$this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );
	}

	public function test_overview_maps_totals_from_the_aggregate_row() {
		$this->client->method( 'post' )->willReturnOnConsecutiveCalls(
			[
				'rows' => [
					[
						'clicks'      => 42,
						'impressions' => 420,
						'ctr'         => 0.1,
						'position'    => 2.5,
					],
				],
			],
			[]
		);

		$overview = $this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );

		$this->assertSame(
			[
				'clicks'      => 42,
				'impressions' => 420,
				'ctr'         => 0.1,
				'position'    => 2.5,
			],
			$overview['totals']
		);
	}

	public function test_overview_returns_empty_totals_when_the_api_returns_no_rows() {
		$this->client->method( 'post' )->willReturn( [] );

		$overview = $this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );

		$this->assertSame(
			[
				'clicks'      => 0,
				'impressions' => 0,
				'ctr'         => null,
				'position'    => null,
			],
			$overview['totals']
		);
	}

	public function test_overview_totals_ctr_and_position_are_null_with_no_impressions() {
		$this->client->method( 'post' )->willReturnOnConsecutiveCalls(
			[
				'rows' => [
					[
						'clicks'      => 0,
						'impressions' => 0,
					],
				],
			],
			[]
		);

		$overview = $this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );

		$this->assertSame(
			[
				'clicks'      => 0,
				'impressions' => 0,
				'ctr'         => null,
				'position'    => null,
			],
			$overview['totals']
		);
	}

	public function test_overview_interval_ctr_and_position_are_null_with_no_impressions() {
		$this->client->method( 'post' )->willReturnOnConsecutiveCalls(
			[],
			[
				'rows' => [
					[
						'keys'        => [ '2026-06-01' ],
						'clicks'      => 0,
						'impressions' => 0,
					],
				],
			]
		);

		$overview = $this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );

		$this->assertSame(
			[
				'clicks'      => 0,
				'impressions' => 0,
				'ctr'         => null,
				'position'    => null,
			],
			$overview['intervals'][0]['subtotals']
		);
	}

	public function test_overview_maps_and_sorts_intervals_by_date_ascending() {
		$this->client->method( 'post' )->willReturnOnConsecutiveCalls(
			[],
			[
				'rows' => [
					[
						'keys'        => [ '2026-06-02' ],
						'clicks'      => 5,
						'impressions' => 50,
						'ctr'         => 0.1,
						'position'    => 3.0,
					],
					[
						'keys'        => [ '2026-06-01' ],
						'clicks'      => 9,
						'impressions' => 90,
						'ctr'         => 0.1,
						'position'    => 1.5,
					],
				],
			]
		);

		$overview = $this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );

		$this->assertSame(
			[
				[
					'interval'  => '2026-06-01',
					'subtotals' => [
						'clicks'      => 9,
						'impressions' => 90,
						'ctr'         => 0.1,
						'position'    => 1.5,
					],
				],
				[
					'interval'  => '2026-06-02',
					'subtotals' => [
						'clicks'      => 5,
						'impressions' => 50,
						'ctr'         => 0.1,
						'position'    => 3.0,
					],
				],
			],
			$overview['intervals']
		);
	}

	public function test_overview_omits_days_with_no_data_rather_than_zero_filling() {
		$this->client->method( 'post' )->willReturnOnConsecutiveCalls(
			[],
			[
				'rows' => [
					[
						'keys'   => [ '2026-06-01' ],
						'clicks' => 1,
					],
				],
			]
		);

		$overview = $this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );

		$this->assertCount( 1, $overview['intervals'] );
	}

	public function test_overview_propagates_api_errors() {
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );

		$this->expectException( SearchConsoleApiException::class );

		$this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );
	}

	public function test_propagates_api_errors() {
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );

		$this->expectException( SearchConsoleApiException::class );

		$this->get_top_queries();
	}

	public function test_get_data_last_updated_returns_the_newest_day_with_data_and_caches_it() {
		$this->options->method( 'get' )->willReturn( null );
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				'sites/https%3A%2F%2Fexample.com%2F/searchAnalytics/query',
				$this->callback( fn( array $body ) => [ 'date' ] === $body['dimensions'] && $body['startDate'] < $body['endDate'] )
			)
			->willReturn(
				[
					'rows' => [
						[ 'keys' => [ '2026-06-22' ] ],
						[ 'keys' => [ '2026-06-24' ] ],
						[ 'keys' => [ '2026-06-23' ] ],
					],
				]
			);
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED,
				$this->callback(
					fn( array $value ) => 'https://example.com/' === $value['property']
						&& '2026-06-24' === $value['date']
						&& abs( time() - $value['checked_at'] ) <= 5
				)
			);

		$this->assertSame( '2026-06-24', $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_ignores_rows_without_a_date() {
		$this->options->method( 'get' )->willReturn( null );
		$this->client->method( 'post' )->willReturn(
			[
				'rows' => [
					[ 'clicks' => 3 ],
					[ 'keys' => [ '2026-06-23' ] ],
				],
			]
		);

		$this->assertSame( '2026-06-23', $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_is_null_when_no_row_has_a_date() {
		$this->options->method( 'get' )->willReturn( null );
		$this->client->method( 'post' )->willReturn( [ 'rows' => [ [ 'clicks' => 3 ] ] ] );

		$this->assertNull( $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_reuses_a_recent_result_without_asking_google() {
		$this->options->method( 'get' )->willReturn(
			[
				'property'   => 'https://example.com/',
				'date'       => '2026-06-24',
				'checked_at' => time() - HOUR_IN_SECONDS,
			]
		);
		$this->client->expects( $this->never() )->method( 'post' );

		$this->assertSame( '2026-06-24', $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_asks_again_once_the_cached_result_is_old() {
		$this->options->method( 'get' )->willReturn(
			[
				'property'   => 'https://example.com/',
				'date'       => '2026-06-20',
				'checked_at' => time() - SearchAnalyticsService::DATA_LAST_UPDATED_CACHE_TIME - 1,
			]
		);
		$this->client->expects( $this->once() )->method( 'post' )->willReturn( [ 'rows' => [ [ 'keys' => [ '2026-06-24' ] ] ] ] );

		$this->assertSame( '2026-06-24', $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_ignores_a_cached_result_for_another_property() {
		$this->options->method( 'get' )->willReturn(
			[
				'property'   => 'https://old.example.com/',
				'date'       => '2026-06-20',
				'checked_at' => time(),
			]
		);
		$this->client->expects( $this->once() )->method( 'post' )->willReturn( [ 'rows' => [ [ 'keys' => [ '2026-06-24' ] ] ] ] );

		$this->assertSame( '2026-06-24', $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_is_null_and_cached_when_there_is_no_data() {
		$this->options->method( 'get' )->willReturn( null );
		$this->client->method( 'post' )->willReturn( [] );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED, $this->callback( fn( array $value ) => null === $value['date'] ) );

		$this->assertNull( $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_falls_back_to_the_old_result_when_google_fails() {
		$this->options->method( 'get' )->willReturn(
			[
				'property'   => 'https://example.com/',
				'date'       => '2026-06-20',
				'checked_at' => time() - SearchAnalyticsService::DATA_LAST_UPDATED_CACHE_TIME - 1,
			]
		);
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED,
				$this->callback( fn( array $value ) => '2026-06-20' === $value['date'] && abs( time() - $value['checked_at'] ) <= 5 )
			);

		$this->assertSame( '2026-06-20', $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_does_not_let_an_unwrapped_network_error_escape() {
		$this->options->method( 'get' )->willReturn( null );
		$this->client->method( 'post' )->willThrowException( new Exception( 'Could not resolve host' ) );

		$this->assertNull( $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_reports_a_failed_lookup_through_the_exception_hook() {
		$this->options->method( 'get' )->willReturn( null );
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );

		$reported = null;
		$listener = function ( $exception, $method ) use ( &$reported ) {
			$reported = [ $exception, $method ];
		};
		add_action( 'woocommerce_gla_exception', $listener, 10, 2 );

		try {
			$this->service->get_data_last_updated( 'https://example.com/' );
		} finally {
			remove_action( 'woocommerce_gla_exception', $listener, 10 );
		}

		$this->assertInstanceOf( SearchConsoleApiException::class, $reported[0] );
		$this->assertSame( SearchAnalyticsService::class . '::get_data_last_updated', $reported[1] );
	}

	public function test_get_data_last_updated_is_null_and_not_retried_straight_away_when_google_fails_and_nothing_was_cached() {
		$this->options->method( 'get' )->willReturn( null );
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED, $this->callback( fn( array $value ) => null === $value['date'] ) );

		$this->assertNull( $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	/**
	 * @dataProvider provide_filtered_limits
	 *
	 * @param mixed $filtered Value returned by a report's limit filter.
	 * @param int   $expected Limit the report uses.
	 */
	public function test_validates_a_filtered_limit_the_same_way_for_every_report( $filtered, int $expected ) {
		$this->assertSame( $expected, SearchAnalyticsService::validate_filtered_limit( $filtered, 100 ) );
	}

	public function provide_filtered_limits(): array {
		return [
			'not a number'      => [ 'many', 100 ],
			'zero'              => [ 0, 1 ],
			'negative'          => [ -5, 1 ],
			'numeric string'    => [ '250', 250 ],
			'above the maximum' => [ 99999, SearchAnalyticsService::API_MAX_ROW_LIMIT ],
		];
	}
}
