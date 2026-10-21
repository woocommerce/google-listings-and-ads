<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchAnalyticsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
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

	public function test_overview_records_a_data_fetch_for_each_underlying_query() {
		// Two Search Analytics requests (totals, intervals) each record their own fetch via the
		// same path get_top_queries() uses -- a harmless double write, not worth a special case.
		$this->client->method( 'post' )->willReturn( [] );

		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->with( OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED );

		$this->service->get_overview( 'https://example.com/', new DateTime( '2026-06-01' ), new DateTime( '2026-06-28' ) );
	}

	public function test_propagates_api_errors() {
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );

		$this->expectException( SearchConsoleApiException::class );

		$this->get_top_queries();
	}

	public function test_records_when_data_was_fetched_for_the_property() {
		$this->client->method( 'post' )->willReturn( [] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED,
				$this->callback(
					function ( $value ) {
						return 'https://example.com/' === $value['property']
							&& abs( time() - $value['timestamp'] ) <= 5;
					}
				)
			);

		$this->get_top_queries();
	}

	public function test_does_not_record_a_fetch_when_the_api_fails() {
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );

		$this->options->expects( $this->never() )->method( 'update' );

		try {
			$this->get_top_queries();
		} catch ( SearchConsoleApiException $e ) {
			// Expected; only the absence of a recorded fetch is under test.
			unset( $e );
		}
	}

	public function test_get_data_last_updated_returns_the_timestamp_for_the_same_property() {
		$this->options->method( 'get' )
			->with( OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED )
			->willReturn(
				[
					'property'  => 'https://example.com/',
					'timestamp' => 1792108800,
				]
			);

		$this->assertSame( 1792108800, $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_ignores_a_timestamp_for_another_property() {
		$this->options->method( 'get' )->willReturn(
			[
				'property'  => 'https://old.example.com/',
				'timestamp' => 1792108800,
			]
		);

		$this->assertNull( $this->service->get_data_last_updated( 'https://example.com/' ) );
	}

	public function test_get_data_last_updated_returns_null_when_nothing_was_fetched() {
		$this->options->method( 'get' )->willReturn( null );

		$this->assertNull( $this->service->get_data_last_updated( 'https://example.com/' ) );
	}
}
