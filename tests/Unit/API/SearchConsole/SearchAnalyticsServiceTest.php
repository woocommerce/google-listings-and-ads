<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchAnalyticsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
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

	/** @var SearchAnalyticsService */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client  = $this->createMock( SearchConsoleApiClient::class );
		$this->service = new SearchAnalyticsService( $this->client );
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

	public function test_propagates_api_errors() {
		$this->client->method( 'post' )->willThrowException( new SearchConsoleApiException( 500, [], 'method' ) );

		$this->expectException( SearchConsoleApiException::class );

		$this->get_top_queries();
	}
}
