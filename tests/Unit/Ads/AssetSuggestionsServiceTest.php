<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AssetSuggestionsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 *
 * @property MockObject|WP  $wp
 */
class AssetSuggestionsServiceTest extends UnitTest {

	protected const DEFAULT_PER_PAGE       = 30;
	protected const DEFAULT_PER_PAGE_POSTS = 15;
	protected const EMPTY_SEARCH           = '';
	protected const TEST_SEARCH            = 'mySearch';

	protected const TEST_POST_TYPES               = [
		'post',
		'page',
		'product',
		'attachment',
	];
	protected const TEST_POST_TYPES_NO_ATTACHMENT = [
		'post',
		'page',
		'product',
	];

	protected const TEST_TAXONOMIES = [
		'taxonomy_1',
		'taxonomy_2',
		'taxonomy_3',
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp                = $this->createMock( WP::class );
		$this->asset_suggestions = new AssetSuggestionsService( $this->wp );

		$this->post = $this->factory()->post->create_and_get( [ 'post_title' => 'Abcd' ] );
		$this->term = $this->factory()->term->create_and_get( [ 'name' => 'bcde' ] );

		$this->suggested_post = $this->format_url_post_item( $this->post );
		$this->suggested_term = $this->format_url_term_item( $this->term );

	}

	public function format_url_post_item( $post ) {
		return [
			'id'    => $post->ID,
			'type'  => 'post',
			'title' => $post->post_title,
			'url'   => get_permalink( $post->ID ),
		];
	}

	public function format_url_term_item( $term ) {
		return [
			'id'    => $term->term_id,
			'type'  => 'term',
			'title' => $term->name,
			'url'   => get_term_link( $term->term_id, $term->taxonomy ),
		];
	}

	public function test_get_post_suggestions() {
		$this->wp->expects( $this->once() )
			->method( 'get_post_types' )
			->willReturn( self::TEST_POST_TYPES );

		// Should be called without the attachment type
		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->with(
				[
					'post_type'      => self::TEST_POST_TYPES_NO_ATTACHMENT,
					'posts_per_page' => self::DEFAULT_PER_PAGE_POSTS,
					'post_status'    => 'publish',
					's'              => self::TEST_SEARCH,
					'offset'         => 0,
				]
			)
			->willReturn( [ $this->post ] );

			$this->wp->expects( $this->once() )
			->method( 'get_taxonomies' );

			$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->willReturn( [] );

		$this->assertEquals( [ $this->suggested_post ], $this->asset_suggestions->get_final_url_suggestions( self::TEST_SEARCH ) );
	}

	public function test_get_term_suggestions() {
		$this->wp->expects( $this->once() )
			->method( 'get_post_types' )
			->willReturn( self::TEST_POST_TYPES );

		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->willReturn( [ $this->post ] );

		$this->wp->expects( $this->once() )
			->method( 'get_taxonomies' )
			->willReturn( self::TEST_TAXONOMIES );

		$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->with(
				[
					'taxonomy'   => self::TEST_TAXONOMIES,
					'hide_empty' => false,
					'number'     => self::DEFAULT_PER_PAGE - 1,
					'name__like' => self::TEST_SEARCH,
				]
			)
			->willReturn( [ $this->term ] );

		$this->assertEquals( [ $this->suggested_post, $this->suggested_term ], $this->asset_suggestions->get_final_url_suggestions( self::TEST_SEARCH ) );
	}

	public function test_get_urls_suggestions_with_no_posts_results() {
		$this->wp->expects( $this->once() )
			->method( 'get_post_types' )
			->willReturn( self::TEST_POST_TYPES );

		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->willReturn( [] );

		$this->wp->expects( $this->once() )
			->method( 'get_taxonomies' )
			->willReturn( self::TEST_TAXONOMIES );

		// Should try to retrieve all results from the terms
		$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->with(
				[
					'taxonomy'   => self::TEST_TAXONOMIES,
					'hide_empty' => false,
					'number'     => self::DEFAULT_PER_PAGE,
					'name__like' => self::TEST_SEARCH,
				]
			)
			->willReturn( [ $this->term ] );

		$this->assertEquals( [ $this->suggested_term ], $this->asset_suggestions->get_final_url_suggestions( self::TEST_SEARCH ) );
	}

	public function test_get_urls_suggestions_order_by_title() {
		$post = $this->factory()->post->create_and_get( [ 'post_title' => 'ZAbcd' ] );
		$term = $this->factory()->term->create_and_get( [ 'name' => 'Abcde' ] );

		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->willReturn(
				[
					$post,
				]
			);

		// Should try to retrieve all results from the terms
		$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->willReturn(
				[
					$term,
				]
			);

		// Term item should go first
		$this->assertEquals( [ $this->format_url_term_item( $term ), $this->format_url_post_item( $post ) ], $this->asset_suggestions->get_final_url_suggestions( self::TEST_SEARCH ) );
	}

	public function test_get_default_urls() {
		$homepage = $this->factory()->post->create_and_get( [ 'post_title' => 'Homepage' ] );
		$shop     = $this->factory()->post->create_and_get( [ 'post_title' => 'Shop' ] );

		$this->wp->expects( $this->once() )
			->method( 'get_static_homepage' )
			->willReturn(
				$homepage
			);

		$this->wp->expects( $this->once() )
			->method( 'get_shop_page' )
			->willReturn(
				$shop
			);

		$this->assertEquals( [ $this->format_url_post_item( $homepage ), $this->format_url_post_item( $shop ) ], $this->asset_suggestions->get_final_url_suggestions() );
	}


	public function test_get_extra_urls_results() {
		$per_page       = 5;
		$per_page_posts = 3;
		$post           = $this->factory()->post->create_and_get();
		$posts_ids      = $this->factory()->post->create_many( $per_page_posts );

		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'get_posts' )
			->willReturnOnConsecutiveCalls(
				get_posts(
					[
						'include' => $posts_ids,
					]
				),
				[ $post ]
			);

		// Should try to retrieve all results from the terms
		$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->willReturn(
				[]
			);

		$expected = [ $this->format_url_post_item( $post ) ];

		foreach ( $posts_ids as $post_id ) {
			$expected[] = $this->format_url_post_item( get_post( $post_id ) );
		}

		$this->assertEquals( $expected, $this->asset_suggestions->get_final_url_suggestions( self::TEST_SEARCH, $per_page ) );
	}
}
