<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\PagePostResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\SearchAnalyticsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\TopStorePagesService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopStorePagesServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class TopStorePagesServiceTest extends UnitTest {

	protected const FILTER = 'woocommerce_gla_search_console_top_store_pages_limit';

	protected const PROPERTY = 'sc-domain:example.com';

	/** @var MockObject|SearchAnalyticsService $search_analytics */
	protected $search_analytics;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var TopStorePagesService $service */
	protected $service;

	public function setUp(): void {
		parent::setUp();

		$this->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$this->search_analytics = $this->createMock( SearchAnalyticsService::class );
		$this->transients       = $this->createMock( TransientsInterface::class );

		$this->service = new TopStorePagesService( $this->search_analytics, new PagePostResolver(), $this->transients );
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

	protected function create_page( string $title, string $slug ): int {
		return $this->factory()->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
			]
		);
	}

	protected function get_top_store_pages( array $rows, ?int $limit = null ): array {
		$this->search_analytics->method( 'query_search_analytics' )->willReturn( [ 'rows' => $rows ] );

		return $this->service->get_top_store_pages( self::PROPERTY, new DateTimeImmutable( '2026-09-01' ), new DateTimeImmutable( '2026-09-30' ), $limit );
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

		$this->assertSame( [], $this->service->get_top_store_pages( self::PROPERTY, new DateTimeImmutable( '2026-09-01' ), new DateTimeImmutable( '2026-09-30' ) ) );
	}

	public function test_returns_pages_by_name_with_metrics_most_clicks_first() {
		$about   = $this->create_page( 'About us', 'about' );
		$contact = $this->create_page( 'Contact', 'contact' );

		$result = $this->get_top_store_pages(
			[
				$this->row( get_permalink( $about ), 5, 50, 4.0 ),
				$this->row( get_permalink( $contact ), 20, 200, 2.0 ),
			]
		);

		$this->assertSame(
			[
				[
					'post_id'     => $contact,
					'name'        => 'Contact',
					'url'         => get_permalink( $contact ),
					'clicks'      => 20,
					'impressions' => 200,
					'ctr'         => 0.1,
					'position'    => 2.0,
				],
				[
					'post_id'     => $about,
					'name'        => 'About us',
					'url'         => get_permalink( $about ),
					'clicks'      => 5,
					'impressions' => 50,
					'ctr'         => 0.1,
					'position'    => 4.0,
				],
			],
			$result
		);
	}

	public function test_names_a_page_by_its_plain_text_title() {
		$page_id = $this->create_page( 'Tom & Jerry\'s "shop"', 'tom-and-jerry' );

		$result = $this->get_top_store_pages( [ $this->row( get_permalink( $page_id ), 1 ) ] );

		$this->assertSame( 'Tom & Jerry\'s "shop"', $result[0]['name'] );
	}

	public function test_names_a_private_page_without_a_private_prefix() {
		$page_id = $this->create_page( 'Members area', 'members' );
		wp_update_post(
			[
				'ID'          => $page_id,
				'post_status' => 'private',
			]
		);

		$result = $this->get_top_store_pages( [ $this->row( home_url( '/?page_id=' . $page_id ), 1 ) ] );

		$this->assertSame( 'Members area', $result[0]['name'] );
	}

	public function test_leaves_out_product_and_variation_pages() {
		$product   = WC_Helper_Product::create_simple_product();
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = $variable->get_children()[0];
		$page_id   = $this->create_page( 'About us', 'about' );

		$result = $this->get_top_store_pages(
			[
				$this->row( home_url( '/?p=' . $product->get_id() ), 50 ),
				$this->row( home_url( '/?p=' . $variation ), 40 ),
				$this->row( get_permalink( $page_id ), 1 ),
			]
		);

		$this->assertSame( [ $page_id ], array_column( $result, 'post_id' ) );
	}

	public function test_leaves_out_a_product_url_whose_slug_changed() {
		$product  = WC_Helper_Product::create_simple_product();
		$old_slug = get_post_field( 'post_name', $product->get_id() );

		wp_update_post(
			[
				'ID'        => $product->get_id(),
				'post_name' => 'renamed-' . $old_slug,
			]
		);

		$this->assertSame( [], $this->get_top_store_pages( [ $this->row( home_url( '/product/' . $old_slug . '/' ), 10 ) ] ) );
	}

	public function test_resolves_a_post_url_whose_slug_changed_to_the_current_post() {
		$post_id = $this->factory()->post->create(
			[
				'post_status' => 'publish',
				'post_title'  => 'Summer sale',
				'post_name'   => 'spring-sale',
			]
		);
		$old_url = get_permalink( $post_id );

		wp_update_post(
			[
				'ID'        => $post_id,
				'post_name' => 'summer-sale',
			]
		);

		$result = $this->get_top_store_pages(
			[
				$this->row( $old_url, 3, 30, 5.0 ),
				$this->row( get_permalink( $post_id ), 7, 70, 1.0 ),
			]
		);

		$this->assertCount( 1, $result );
		$this->assertSame( $post_id, $result[0]['post_id'] );
		$this->assertSame( 'Summer sale', $result[0]['name'] );
		$this->assertSame( get_permalink( $post_id ), $result[0]['url'] );
		$this->assertSame( 10, $result[0]['clicks'] );
		$this->assertSame( 100, $result[0]['impressions'] );
		$this->assertEqualsWithDelta( 2.2, $result[0]['position'], 0.0001 );
	}

	public function test_keeps_a_store_url_that_is_not_a_post_named_by_its_path() {
		$url = home_url( '/product-category/caf%C3%A9/' );

		$result = $this->get_top_store_pages( [ $this->row( $url, 4 ) ] );

		$this->assertSame(
			[
				'post_id' => null,
				'name'    => '/product-category/café/',
				'url'     => $url,
			],
			array_intersect_key( $result[0], array_flip( [ 'post_id', 'name', 'url' ] ) )
		);
	}

	public function test_names_a_store_url_that_is_not_a_post_by_its_path_and_query() {
		$result = $this->get_top_store_pages( [ $this->row( home_url( '/shop/?orderby=price' ), 3 ) ] );

		$this->assertSame( '/shop/?orderby=price', $result[0]['name'] );
	}

	public function test_leaves_out_a_plain_permalink_product_url() {
		$this->set_permalink_structure( '' );

		$product = WC_Helper_Product::create_simple_product();
		$slug    = get_post_field( 'post_name', $product->get_id() );

		$result = $this->get_top_store_pages(
			[
				$this->row( home_url( '/?product=' . $slug ), 30 ),
				$this->row( home_url( '/?product_cat=clothing' ), 2 ),
			]
		);

		$this->assertSame( [ '/?product_cat=clothing' ], array_column( $result, 'name' ) );
	}

	public function test_leaves_out_a_url_on_another_host() {
		$this->create_page( 'About us', 'about' );

		$this->assertSame( [], $this->get_top_store_pages( [ $this->row( 'https://other.example/about/', 9 ) ] ) );
	}

	public function test_caps_the_result_at_the_limit() {
		$rows = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$rows[] = $this->row( home_url( '/archive-' . $i . '/' ), $i );
		}

		$this->assertSame( [ 5, 4 ], array_column( $this->get_top_store_pages( $rows, 2 ), 'clicks' ) );
	}

	public function test_defaults_the_limit_to_one_hundred() {
		$this->assertSame( 100, $this->service->get_top_store_pages_limit() );
	}

	public function test_a_filter_can_raise_the_limit() {
		add_filter( self::FILTER, fn() => 250 );

		$this->assertSame( 250, $this->service->get_top_store_pages_limit() );
	}

	public function test_a_filtered_limit_that_is_not_a_number_falls_back_to_the_default() {
		add_filter( self::FILTER, fn() => 'many' );

		$this->assertSame( 100, $this->service->get_top_store_pages_limit() );
	}

	public function test_caches_the_result_in_its_own_transient() {
		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with( TransientsInterface::TOP_STORE_PAGES, $this->isType( 'array' ), HOUR_IN_SECONDS );

		$this->get_top_store_pages( [] );
	}
}
