<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\PageProductResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchAnalyticsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopProductPagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopProductPagesServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class TopProductPagesServiceTest extends UnitTest {

	protected const FILTER = 'woocommerce_gla_search_console_top_product_pages_limit';

	protected const PROPERTY = 'sc-domain:example.com';

	/** @var MockObject|SearchAnalyticsService $search_analytics */
	protected $search_analytics;

	/** @var MockObject|PageProductResolver $resolver */
	protected $resolver;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var TopProductPagesService $service */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->search_analytics = $this->createMock( SearchAnalyticsService::class );
		$this->resolver         = $this->createMock( PageProductResolver::class );
		$this->product_helper   = $this->createMock( ProductHelper::class );
		$this->transients       = $this->createMock( TransientsInterface::class );

		$this->product_helper->method( 'get_wc_product' )->willReturnCallback(
			function ( int $product_id ) {
				$product = $this->createMock( WC_Product::class );
				$product->method( 'get_name' )->willReturn( 'Product ' . $product_id );

				return $product;
			}
		);

		$this->service = new TopProductPagesService( $this->search_analytics, $this->resolver, $this->product_helper, $this->transients );
	}

	public function tearDown(): void {
		remove_all_filters( self::FILTER );

		parent::tearDown();
	}

	protected function date( string $date ): DateTimeImmutable {
		return new DateTimeImmutable( $date );
	}

	protected function row( string $url, int $clicks, int $impressions = 100, float $position = 3.0 ): array {
		return [
			'keys'        => [ $url ],
			'clicks'      => $clicks,
			'impressions' => $impressions,
			'ctr'         => $impressions ? $clicks / $impressions : 0,
			'position'    => $position,
		];
	}

	/**
	 * Resolve `https://example.com/p/{id}/` style URLs to that id, and `/unknown/` URLs to nothing.
	 */
	protected function resolve_urls_by_trailing_id(): void {
		$this->resolver->method( 'resolve_product_id' )->willReturnCallback(
			function ( string $url ) {
				return preg_match( '#/p/(\d+)/?$#', $url, $match ) ? (int) $match[1] : null;
			}
		);
	}

	public function test_queries_the_connected_property_by_page_with_an_over_fetched_row_limit() {
		$this->search_analytics->expects( $this->once() )
			->method( 'query_search_analytics' )
			->with(
				self::PROPERTY,
				[
					'startDate'  => '2026-09-01',
					'endDate'    => '2026-09-30',
					'dimensions' => [ 'page' ],
					'rowLimit'   => 300,
				]
			)
			->willReturn( [ 'rows' => [] ] );

		$this->assertSame( [], $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) ) );
	}

	public function test_returns_products_with_names_and_metrics_most_clicks_first() {
		$this->resolve_urls_by_trailing_id();
		$this->search_analytics->method( 'query_search_analytics' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/p/1/', 5, 50, 4.0 ),
					$this->row( 'https://example.com/p/2/', 20, 200, 2.0 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );

		$this->assertSame(
			[
				[
					'product_id'  => 2,
					'name'        => 'Product 2',
					'clicks'      => 20,
					'impressions' => 200,
					'ctr'         => 0.1,
					'position'    => 2.0,
				],
				[
					'product_id'  => 1,
					'name'        => 'Product 1',
					'clicks'      => 5,
					'impressions' => 50,
					'ctr'         => 0.1,
					'position'    => 4.0,
				],
			],
			$result
		);
	}

	public function test_excludes_rows_that_do_not_resolve_to_a_product() {
		$this->resolve_urls_by_trailing_id();
		$this->search_analytics->method( 'query_search_analytics' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/unknown/', 99 ),
					$this->row( 'https://example.com/p/1/', 5 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );

		$this->assertSame( [ 1 ], array_column( $result, 'product_id' ) );
	}

	public function test_combines_pages_that_resolve_to_the_same_product() {
		$this->resolve_urls_by_trailing_id();
		$this->search_analytics->method( 'query_search_analytics' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/p/1/', 10, 100, 2.0 ),
					$this->row( 'https://example.com/old/p/1/', 10, 300, 6.0 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 20, $result[0]['clicks'] );
		$this->assertSame( 400, $result[0]['impressions'] );
		$this->assertEqualsWithDelta( 0.05, $result[0]['ctr'], 0.0001 );
		$this->assertEqualsWithDelta( 5.0, $result[0]['position'], 0.0001 );
	}

	public function test_reaches_the_cap_even_when_some_urls_do_not_resolve() {
		add_filter(
			self::FILTER,
			function () {
				return 2;
			}
		);
		$this->resolve_urls_by_trailing_id();
		$this->search_analytics->method( 'query_search_analytics' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/unknown/', 90 ),
					$this->row( 'https://example.com/p/1/', 40 ),
					$this->row( 'https://example.com/p/2/', 30 ),
					$this->row( 'https://example.com/p/3/', 20 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );

		$this->assertSame( [ 1, 2 ], array_column( $result, 'product_id' ) );
	}

	public function test_returns_100_products_by_default_when_some_urls_do_not_resolve() {
		$this->resolve_urls_by_trailing_id();

		$rows = [];
		for ( $i = 1; $i <= 140; $i++ ) {
			// Every fourth page is not a product, and clicks fall as the id rises.
			$rows[] = 0 === $i % 4
				? $this->row( 'https://example.com/unknown/' . $i . '/', 1000 - $i )
				: $this->row( 'https://example.com/p/' . $i . '/', 1000 - $i );
		}
		$this->search_analytics->method( 'query_search_analytics' )->willReturn( [ 'rows' => $rows ] );

		$result = $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );

		$this->assertCount( 100, $result );
		$this->assertSame( 1, $result[0]['product_id'] );
		$this->assertNotContains( 4, array_column( $result, 'product_id' ) );
	}

	public function test_the_limit_filter_raises_the_cap_and_the_requested_row_limit() {
		add_filter(
			self::FILTER,
			function () {
				return 150;
			}
		);

		$this->search_analytics->expects( $this->once() )
			->method( 'query_search_analytics' )
			->with( $this->anything(), $this->callback( fn( array $body ) => 450 === $body['rowLimit'] ) )
			->willReturn( [ 'rows' => [] ] );

		$this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );
	}

	public function test_a_non_numeric_filtered_limit_falls_back_to_the_default() {
		add_filter( self::FILTER, fn() => 'many' );

		$this->assertSame( 100, $this->service->get_top_product_pages_limit() );
	}

	/**
	 * @dataProvider provide_limits_to_clamp
	 *
	 * @param int $filtered Value returned by the filter.
	 * @param int $expected Limit the service uses.
	 */
	public function test_the_filtered_limit_is_kept_within_the_api_range( int $filtered, int $expected ) {
		add_filter( self::FILTER, fn() => $filtered );

		$this->assertSame( $expected, $this->service->get_top_product_pages_limit() );
	}

	public function provide_limits_to_clamp(): array {
		return [
			'zero'     => [ 0, 1 ],
			'negative' => [ -5, 1 ],
			'too high' => [ 99999, 25000 ],
		];
	}

	public function test_an_explicit_limit_is_used_without_reading_the_filter() {
		add_filter( self::FILTER, fn() => 500 );

		$this->search_analytics->expects( $this->once() )
			->method( 'query_search_analytics' )
			->with( $this->anything(), $this->callback( fn( array $body ) => 60 === $body['rowLimit'] ) )
			->willReturn( [ 'rows' => [] ] );

		$this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ), 20 );
	}

	public function test_caches_the_result_for_a_property_range_and_limit() {
		$this->resolve_urls_by_trailing_id();
		$this->search_analytics->method( 'query_search_analytics' )->willReturn( [ 'rows' => [ $this->row( 'https://example.com/p/1/', 5 ) ] ] );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with(
				TransientsInterface::TOP_PRODUCT_PAGES,
				$this->callback( fn( array $cached ) => 1 === count( $cached ) && 1 === count( reset( $cached )['products'] ) && reset( $cached )['expires'] > time() ),
				HOUR_IN_SECONDS
			);

		$this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );
	}

	public function test_keeps_only_the_most_recent_cached_results() {
		$stale = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$stale[ 'old-' . $i ] = [
				'expires'  => time() + 600,
				'products' => [],
			];
		}

		$this->resolve_urls_by_trailing_id();
		$this->transients->method( 'get' )->willReturn( $stale );
		$this->search_analytics->method( 'query_search_analytics' )->willReturn( [ 'rows' => [ $this->row( 'https://example.com/p/1/', 5 ) ] ] );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with(
				TransientsInterface::TOP_PRODUCT_PAGES,
				$this->callback( fn( array $cached ) => 10 === count( $cached ) && ! isset( $cached['old-0'] ) && isset( $cached['old-9'] ) ),
				HOUR_IN_SECONDS
			);

		$this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );
	}

	public function test_returns_the_cached_result_without_querying_search_console() {
		$key    = md5( implode( '|', [ self::PROPERTY, '2026-09-01', '2026-09-30', 100 ] ) );
		$cached = [
			[
				'product_id'  => 3,
				'name'        => 'Cached',
				'clicks'      => 1,
				'impressions' => 2,
				'ctr'         => 0.5,
				'position'    => 1.0,
			],
		];

		$this->transients->method( 'get' )->willReturn(
			[
				$key => [
					'expires'  => time() + 600,
					'products' => $cached,
				],
			]
		);
		$this->search_analytics->expects( $this->never() )->method( 'query_search_analytics' );
		$this->transients->expects( $this->never() )->method( 'set' );

		$this->assertSame( $cached, $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) ) );
	}

	public function test_caches_an_empty_result_for_a_shorter_time() {
		$this->resolve_urls_by_trailing_id();
		$this->search_analytics->method( 'query_search_analytics' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/unknown/', 90 ),
				],
			]
		);

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with(
				TransientsInterface::TOP_PRODUCT_PAGES,
				$this->callback(
					function ( array $cached ) {
						$entry = reset( $cached );

						return [] === $entry['products']
							&& $entry['expires'] > time()
							&& $entry['expires'] <= time() + 5 * MINUTE_IN_SECONDS;
					}
				),
				HOUR_IN_SECONDS
			);

		$this->assertSame( [], $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) ) );
	}

	public function test_returns_a_cached_empty_result_without_querying_search_console() {
		$key = md5( implode( '|', [ self::PROPERTY, '2026-09-01', '2026-09-30', 100 ] ) );

		$this->transients->method( 'get' )->willReturn(
			[
				$key => [
					'expires'  => time() + 60,
					'products' => [],
				],
			]
		);
		$this->search_analytics->expects( $this->never() )->method( 'query_search_analytics' );

		$this->assertSame( [], $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) ) );
	}

	public function test_queries_again_when_the_cached_result_has_expired() {
		$key = md5( implode( '|', [ self::PROPERTY, '2026-09-01', '2026-09-30', 100 ] ) );

		$this->transients->method( 'get' )->willReturn(
			[
				$key => [
					'expires'  => time() - 1,
					'products' => [ [ 'product_id' => 3 ] ],
				],
			]
		);
		$this->search_analytics->expects( $this->once() )->method( 'query_search_analytics' )->willReturn( [ 'rows' => [] ] );

		$this->assertSame( [], $this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) ) );
	}

	public function test_drops_expired_entries_when_writing_the_cache() {
		$this->transients->method( 'get' )->willReturn(
			[
				'expired' => [
					'expires'  => time() - 1,
					'products' => [],
				],
				'legacy'  => [],
			]
		);
		$this->resolve_urls_by_trailing_id();
		$this->search_analytics->method( 'query_search_analytics' )->willReturn( [ 'rows' => [ $this->row( 'https://example.com/p/1/', 5 ) ] ] );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with(
				TransientsInterface::TOP_PRODUCT_PAGES,
				$this->callback( fn( array $cached ) => 1 === count( $cached ) && ! isset( $cached['expired'] ) && ! isset( $cached['legacy'] ) ),
				HOUR_IN_SECONDS
			);

		$this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );
	}

	public function test_records_when_the_data_was_fetched_for_the_property() {
		$client  = $this->createMock( SearchConsoleApiClient::class );
		$options = $this->createMock( OptionsInterface::class );

		$client->method( 'post' )->willReturn( [ 'rows' => [] ] );
		$options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED,
				$this->callback( fn( array $value ) => self::PROPERTY === $value['property'] && $value['timestamp'] > 0 )
			);

		$search_analytics = new SearchAnalyticsService( $client );
		$search_analytics->set_options_object( $options );

		$service = new TopProductPagesService( $search_analytics, $this->resolver, $this->product_helper, $this->transients );
		$service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );
	}

	public function test_does_not_record_a_fetch_when_the_result_comes_from_the_cache() {
		$key = md5( implode( '|', [ self::PROPERTY, '2026-09-01', '2026-09-30', 100 ] ) );

		$client  = $this->createMock( SearchConsoleApiClient::class );
		$options = $this->createMock( OptionsInterface::class );

		$this->transients->method( 'get' )->willReturn(
			[
				$key => [
					'expires'  => time() + 600,
					'products' => [],
				],
			]
		);
		$client->expects( $this->never() )->method( 'post' );
		$options->expects( $this->never() )->method( 'update' );

		$search_analytics = new SearchAnalyticsService( $client );
		$search_analytics->set_options_object( $options );

		$service = new TopProductPagesService( $search_analytics, $this->resolver, $this->product_helper, $this->transients );
		$service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );
	}

	public function test_propagates_a_search_console_api_error() {
		$this->search_analytics->method( 'query_search_analytics' )->willThrowException(
			new SearchConsoleApiException( 403, [ 'error' => [ 'message' => 'Forbidden' ] ], __METHOD__ )
		);

		$this->expectException( SearchConsoleApiException::class );

		$this->service->get_top_product_pages( self::PROPERTY, $this->date( '2026-09-01' ), $this->date( '2026-09-30' ) );
	}
}
