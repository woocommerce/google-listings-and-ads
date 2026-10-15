<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\PageProductResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchConsoleApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopProductPagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
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

	/** @var MockObject|SearchConsoleApiClient $client */
	protected $client;

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var MockObject|PageProductResolver $resolver */
	protected $resolver;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var TopProductPagesService $service */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->client         = $this->createMock( SearchConsoleApiClient::class );
		$this->connection     = $this->createMock( Connection::class );
		$this->resolver       = $this->createMock( PageProductResolver::class );
		$this->product_helper = $this->createMock( ProductHelper::class );

		$this->connection->method( 'get_connection_data' )->willReturn( [ 'property' => self::PROPERTY ] );
		$this->product_helper->method( 'get_wc_product' )->willReturnCallback(
			function ( int $product_id ) {
				$product = $this->createMock( WC_Product::class );
				$product->method( 'get_name' )->willReturn( 'Product ' . $product_id );

				return $product;
			}
		);

		$this->service = new TopProductPagesService( $this->client, $this->connection, $this->resolver, $this->product_helper );
	}

	public function tearDown(): void {
		remove_all_filters( self::FILTER );

		parent::tearDown();
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

	public function test_throws_when_no_property_is_connected() {
		$connection = $this->createMock( Connection::class );
		$connection->method( 'get_connection_data' )->willReturn( [ 'property' => null ] );
		$service = new TopProductPagesService( $this->client, $connection, $this->resolver, $this->product_helper );

		$this->client->expects( $this->never() )->method( 'post' );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$service->get_top_product_pages( '2026-09-01', '2026-09-30' );
	}

	public function test_queries_the_connected_property_by_page_with_an_over_fetched_row_limit() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				'sites/' . rawurlencode( self::PROPERTY ) . '/searchAnalytics/query',
				[
					'startDate'  => '2026-09-01',
					'endDate'    => '2026-09-30',
					'dimensions' => [ 'page' ],
					'rowLimit'   => 300,
				]
			)
			->willReturn( [ 'rows' => [] ] );

		$this->assertSame( [], $this->service->get_top_product_pages( '2026-09-01', '2026-09-30' ) );
	}

	public function test_returns_products_with_names_and_metrics_most_clicks_first() {
		$this->resolve_urls_by_trailing_id();
		$this->client->method( 'post' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/p/1/', 5, 50, 4.0 ),
					$this->row( 'https://example.com/p/2/', 20, 200, 2.0 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( '2026-09-01', '2026-09-30' );

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
		$this->client->method( 'post' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/unknown/', 99 ),
					$this->row( 'https://example.com/p/1/', 5 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( '2026-09-01', '2026-09-30' );

		$this->assertSame( [ 1 ], array_column( $result, 'product_id' ) );
	}

	public function test_combines_pages_that_resolve_to_the_same_product() {
		$this->resolve_urls_by_trailing_id();
		$this->client->method( 'post' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/p/1/', 10, 100, 2.0 ),
					$this->row( 'https://example.com/old/p/1/', 10, 300, 6.0 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( '2026-09-01', '2026-09-30' );

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
		$this->client->method( 'post' )->willReturn(
			[
				'rows' => [
					$this->row( 'https://example.com/unknown/', 90 ),
					$this->row( 'https://example.com/p/1/', 40 ),
					$this->row( 'https://example.com/p/2/', 30 ),
					$this->row( 'https://example.com/p/3/', 20 ),
				],
			]
		);

		$result = $this->service->get_top_product_pages( '2026-09-01', '2026-09-30' );

		$this->assertSame( [ 1, 2 ], array_column( $result, 'product_id' ) );
	}

	public function test_the_limit_filter_raises_the_cap_and_the_requested_row_limit() {
		add_filter(
			self::FILTER,
			function () {
				return 150;
			}
		);

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->anything(), $this->callback( fn( array $body ) => 450 === $body['rowLimit'] ) )
			->willReturn( [ 'rows' => [] ] );

		$this->service->get_top_product_pages( '2026-09-01', '2026-09-30' );
	}

	/**
	 * @dataProvider provide_invalid_limits
	 *
	 * @param mixed $limit A value a filter callback might return.
	 */
	public function test_an_invalid_filtered_limit_falls_back_to_the_default( $limit ) {
		add_filter(
			self::FILTER,
			function () use ( $limit ) {
				return $limit;
			}
		);

		$this->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->anything(), $this->callback( fn( array $body ) => 300 === $body['rowLimit'] ) )
			->willReturn( [ 'rows' => [] ] );

		$this->service->get_top_product_pages( '2026-09-01', '2026-09-30' );
	}

	public function provide_invalid_limits(): array {
		return [
			'zero'     => [ 0 ],
			'negative' => [ -5 ],
			'string'   => [ 'many' ],
			'null'     => [ null ],
			'array'    => [ [] ],
		];
	}

	public function test_propagates_a_search_console_api_error() {
		$this->client->method( 'post' )->willThrowException(
			new SearchConsoleApiException( 403, [ 'error' => [ 'message' => 'Forbidden' ] ], __METHOD__ )
		);

		$this->expectException( SearchConsoleApiException::class );

		$this->service->get_top_product_pages( '2026-09-01', '2026-09-30' );
	}
}
