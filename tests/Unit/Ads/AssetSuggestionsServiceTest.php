<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ArrayUtil;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DimensionUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ImageUtility;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;
use WC_Helper_Product;


defined( 'ABSPATH' ) || exit;

/**
 * Class AssetSuggestionsServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 *
 * @property MockObject|WP  $wp
 */
class AssetSuggestionsServiceTest extends UnitTest {

	use DataTrait;

	protected const DEFAULT_PER_PAGE                 = 30;
	protected const DEFAULT_PER_PAGE_POSTS           = 15;
	protected const EMPTY_SEARCH                     = '';
	protected const TEST_SEARCH                      = 'mySearch';
	protected const DEFAULT_MAXIMUM_MARKETING_IMAGES = 20;
	protected const INVALID_ID                       = 123456;
	protected const SQUARE_MARKETING_IMAGE_KEY       = 'gla_square_marketing_asset';
	protected const MARKETING_IMAGE_KEY              = 'gla_marketing_asset';
	protected const LOGO_IMAGE_KEY                   = 'gla_logo_asset';


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

		$this->wp            = $this->createMock( WP::class );
		$this->wc            = $this->createMock( WC::class );
		$this->image_utility = $this->createMock( ImageUtility::class );

		$this->asset_suggestions = new AssetSuggestionsService( $this->wp, $this->wc, $this->image_utility );

		$this->post = $this->factory()->post->create_and_get( [ 'post_title' => 'Abcd' ] );
		$this->term = $this->factory()->term->create_and_get( [ 'name' => 'bcde' ] );

		$this->suggested_post = $this->format_url_post_item( $this->post );
		$this->suggested_term = $this->format_url_term_item( $this->term );

		$this->big_image                 = new DimensionUtility( 2000, 2000 );
		$this->small_image               = new DimensionUtility( 50, 50 );
		$this->normal_image              = new DimensionUtility( 600, 600 );
		$this->suggested_image_square    = new DimensionUtility( 1200, 1200 );
		$this->suggested_image_landscape = new DimensionUtility( 1200, 628 );

		// Disable logo image by default. There is a specific test for the logo asset.
		add_filter( 'theme_mod_custom_logo', '__return_false' );

	}

	protected function format_url_post_item( $post ) {
		return [
			'id'    => $post->ID,
			'type'  => 'post',
			'title' => $post->post_title,
			'url'   => get_permalink( $post->ID ),
		];
	}

	protected function format_url_term_item( $term ) {
		return [
			'id'    => $term->term_id,
			'type'  => 'term',
			'title' => $term->name,
			'url'   => get_term_link( $term->term_id, $term->taxonomy ),
		];
	}

	protected function format_post_asset_response( $post, array $marketing_images = [] ): array {
		return [
			'final_url'               => get_permalink( $post->ID ),
			'headline'                => [ $post->post_title ],
			'long_headline'           => [ get_bloginfo( 'name' ) . ': ' . $post->post_title ],
			'description'             => ArrayUtil::remove_empty_values( [ $post->post_excerpt, get_bloginfo( 'description' ) ] ),
			'business_name'           => get_bloginfo( 'name' ),
			'display_url_path'        => [ $post->post_name ],
			'logo'                    => ArrayUtil::remove_empty_values( [ wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ) ) ] ),
			'square_marketing_images' => $marketing_images[ self::SQUARE_MARKETING_IMAGE_KEY ] ?? [],
			'marketing_images'        => $marketing_images[ self::MARKETING_IMAGE_KEY ] ?? [],
			'call_to_action'          => null,
		];

	}

	protected function format_term_asset_response( $term, array $marketing_images = [] ): array {
		return [
			'final_url'               => get_term_link( $term->term_id ),
			'headline'                => [ $term->name ],
			'long_headline'           => [ get_bloginfo( 'name' ) . ': ' . $term->name ],
			'description'             => ArrayUtil::remove_empty_values( [ wp_strip_all_tags( $term->description ), get_bloginfo( 'description' ) ] ),
			'logo'                    => ArrayUtil::remove_empty_values( [ wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ) ) ] ),
			'business_name'           => get_bloginfo( 'name' ),
			'display_url_path'        => [ $term->slug ],
			'square_marketing_images' => $marketing_images[ self::SQUARE_MARKETING_IMAGE_KEY ] ?? [],
			'marketing_images'        => $marketing_images[ self::MARKETING_IMAGE_KEY ] ?? [],
			'call_to_action'          => null,
		];

	}

	protected function update_size_image( int $attachmend_id, DimensionUtility $size, array $size_types = [] ): void {
		$metadata           = wp_get_attachment_metadata( $attachmend_id );
		$metadata['width']  = $size->x;
		$metadata['height'] = $size->y;

		foreach ( $size_types as $size_type ) {
			$metadata['sizes'][ $size_type ] = [
				'file'   => $metadata['file'],
				'width'  => $this->normal_image->x,
				'height' => $this->normal_image->y,
			];
		}

		wp_update_attachment_metadata( $attachmend_id, $metadata );
	}

	protected function get_test_image() {
		return $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );
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

	public function test_get_post_assets() {
		$image_id = $this->get_test_image();

		$this->update_size_image( $image_id, $this->big_image );

		$this->image_utility->expects( $this->exactly( 2 ) )
		->method( 'recommend_size' )
		->willReturnOnConsecutiveCalls( $this->suggested_image_square, $this->suggested_image_landscape );

		// Both images should be resized
		$this->image_utility->expects( $this->exactly( 2 ) )
		->method( 'maybe_add_subsize_image' )
		->willReturn( true );

		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->with(
				[
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'numberposts'    => self::DEFAULT_MAXIMUM_MARKETING_IMAGES,
					'fields'         => 'ids',
					'post_parent'    => $this->post->ID,
				]
			)->willReturn( [ $image_id ] );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_id ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [ wp_get_attachment_image_url( $image_id ) ];

		$this->assertEquals( $this->format_post_asset_response( $this->post, $images ), $this->asset_suggestions->get_assets_suggestions( $this->post->ID, 'post' ) );
	}

	public function test_get_post_assets_for_products() {
		$post     = $this->factory()->post->create_and_get( [ 'post_type' => 'product' ] );
		$image_id = $this->get_test_image();

		// Update size and create both subsize.
		$this->update_size_image( $image_id, $this->normal_image, [ self::SQUARE_MARKETING_IMAGE_KEY, self::MARKETING_IMAGE_KEY ] );

		$this->image_utility->expects( $this->exactly( 2 ) )
		->method( 'recommend_size' )
		->willReturnOnConsecutiveCalls( $this->suggested_image_square, $this->suggested_image_landscape );

		// Should not create any subsize because already exists
		$this->image_utility->expects( $this->exactly( 0 ) )->
		method( 'maybe_add_subsize_image' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_gallery_image_ids( [ $image_id ] );

		$this->wc->expects( $this->once() )
			->method( 'maybe_get_product' )
			->willReturn( $product );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_id, self::SQUARE_MARKETING_IMAGE_KEY ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [ wp_get_attachment_image_url( $image_id, self::MARKETING_IMAGE_KEY ) ];

		$this->assertEquals( $this->format_post_asset_response( $post, $images ), $this->asset_suggestions->get_assets_suggestions( $post->ID, 'post' ) );

	}

	public function test_get_shop_assets() {
		$image_post_id    = $this->get_test_image();
		$image_product_id = $this->get_test_image();

		$this->update_size_image( $image_post_id, $this->normal_image, [ self::SQUARE_MARKETING_IMAGE_KEY, self::MARKETING_IMAGE_KEY ] );
		$this->update_size_image( $image_product_id, $this->normal_image, [ self::SQUARE_MARKETING_IMAGE_KEY, self::MARKETING_IMAGE_KEY ] );

		update_option( 'woocommerce_shop_page_id', $this->post->ID );

		$this->image_utility->expects( $this->exactly( 4 ) )
		->method( 'recommend_size' )
		->willReturn( $this->suggested_image_square );

		// Should not create any subsize because already exists
		$this->image_utility->expects( $this->exactly( 0 ) )->
		method( 'maybe_add_subsize_image' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_gallery_image_ids( [ $image_product_id ] );

		$this->wp->expects( $this->exactly( 3 ) )
			->method( 'get_posts' )
			->willReturnOnConsecutiveCalls( [ $image_post_id ], [ $product->get_id() ], [ $image_product_id ] );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_post_id, self::SQUARE_MARKETING_IMAGE_KEY ), wp_get_attachment_image_url( $image_product_id, self::SQUARE_MARKETING_IMAGE_KEY ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [ wp_get_attachment_image_url( $image_post_id, self::MARKETING_IMAGE_KEY ), wp_get_attachment_image_url( $image_product_id, self::MARKETING_IMAGE_KEY ) ];

		$this->assertEquals( $this->format_post_asset_response( $this->post, $images ), $this->asset_suggestions->get_assets_suggestions( $this->post->ID, 'post' ) );
	}

	public function test_get_invalid_post_id() {
		$this->expectException( Exception::class );
		$this->asset_suggestions->get_assets_suggestions( self::INVALID_ID, 'post' );
	}

	public function test_get_trash_post() {
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'trash' ] );
		$this->expectException( Exception::class );
		$this->asset_suggestions->get_assets_suggestions( $post->ID, 'post' );
	}

	public function test_get_term_with_product() {
		$post         = $this->factory()->post->create_and_get( [ 'post_type' => 'product' ] );
		$image_post_1 = $this->get_test_image();
		$image_post_2 = $this->get_test_image();

		$this->update_size_image( $image_post_1, $this->small_image );
		$this->update_size_image( $image_post_2, $this->normal_image );

		$this->image_utility->expects( $this->exactly( 4 ) )
		->method( 'recommend_size' )
		->willReturn( $this->suggested_image_square );

		// One image is too small and the other one is ok.
		$this->image_utility->expects( $this->exactly( 4 ) )
		->method( 'maybe_add_subsize_image' )
		->willReturnOnConsecutiveCalls( false, false, true, true );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( $image_post_2 );
		$post->ID = $product->get_id();

		$posts_ids_assigned_to_term = [ $this->post, $post ];

		$this->wc->expects( $this->once() )
			->method( 'maybe_get_product' )
			->willReturn( $product );

		$args_posts_assigned_to_term = [
			'post_type'   => 'any',
			'numberposts' => self::DEFAULT_MAXIMUM_MARKETING_IMAGES,
			'tax_query'   => [
				[
					'taxonomy'         => $this->term->taxonomy,
					'terms'            => $this->term->term_id,
					'field'            => 'term_id',
					'include_children' => false,
				],
			],
		];

		$args_post_image_attachments = [
			'post_type'       => 'attachment',
			'post_mime_type'  => 'image',
			'fields'          => 'ids',
			'numberposts'     => self::DEFAULT_MAXIMUM_MARKETING_IMAGES,
			'post_parent__in' => [ $this->post->ID, $product->get_id() ],
		];

		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'get_posts' )
			->withConsecutive(
				[ $args_posts_assigned_to_term ],
				[ $args_post_image_attachments ],
			)
			->willReturnOnConsecutiveCalls( $posts_ids_assigned_to_term, [ $image_post_1, $image_post_2 ] );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_post_2 ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [ wp_get_attachment_image_url( $image_post_2 ) ];

		$this->assertEquals( $this->format_term_asset_response( $this->term, $images ), $this->asset_suggestions->get_assets_suggestions( $this->term->term_id, 'term' ) );

	}

	public function test_get_term_without_product() {
		$post                       = $this->factory()->post->create_and_get();
		$posts_ids_assigned_to_term = [ $this->post, $post ];

		$this->wc->expects( $this->never() )
			->method( 'maybe_get_product' );

		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'get_posts' )
			->willReturnOnConsecutiveCalls( $posts_ids_assigned_to_term, [] );

		$this->assertEquals( $this->format_term_asset_response( $this->term ), $this->asset_suggestions->get_assets_suggestions( $this->term->term_id, 'term' ) );

	}

	public function test_get_term_without_assigned_posts() {
		$posts_ids_assigned_to_term = [];

		$this->wc->expects( $this->never() )
			->method( 'maybe_get_product' );

		$this->wp->expects( $this->exactly( 1 ) )
			->method( 'get_posts' )
			->willReturn( $posts_ids_assigned_to_term );

		$this->assertEquals( $this->format_term_asset_response( $this->term, [] ), $this->asset_suggestions->get_assets_suggestions( $this->term->term_id, 'term' ) );

	}

	public function test_get_invalid_term_id() {
		$this->expectException( Exception::class );
		$this->asset_suggestions->get_assets_suggestions( self::INVALID_ID, 'term' );
	}

	public function test_assets_with_logo() {
		$image_id = $this->get_test_image();

		add_filter(
			'theme_mod_custom_logo',
			function() use ( $image_id ) {
				return $image_id; }
		);

		$this->update_size_image( $image_id, $this->big_image );

		$this->image_utility->expects( $this->exactly( 1 ) )
		->method( 'recommend_size' )
		->willReturn( $this->suggested_image_square );

		$this->image_utility->expects( $this->exactly( 1 ) )
		->method( 'maybe_add_subsize_image' )
		->willReturn( true );

		$images[ self::LOGO_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_id ) ];

		$this->assertEquals( $this->format_post_asset_response( $this->post, $images ), $this->asset_suggestions->get_assets_suggestions( $this->post->ID, 'post' ) );

	}


	public function tests_assets_with_similar_size() {
		$image_id = $this->get_test_image();

		$this->update_size_image( $image_id, $this->suggested_image_square );

		$this->image_utility->expects( $this->exactly( 2 ) )
		->method( 'recommend_size' )
		->willReturn( $this->suggested_image_square );

		// It should not create a subsize because the original image already has the suggested size.
		$this->image_utility->expects( $this->exactly( 0 ) )
		->method( 'maybe_add_subsize_image' );

		$this->wp->expects( $this->exactly( 1 ) )
		->method( 'get_posts' )
		->willReturn( [ $image_id ] );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_id ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [ wp_get_attachment_image_url( $image_id ) ];

		$this->assertEquals( $this->format_post_asset_response( $this->post, $images ), $this->asset_suggestions->get_assets_suggestions( $this->post->ID, 'post' ) );

	}

	public function tests_assets_image_too_small_size() {
		$image_id = $this->get_test_image();

		$this->update_size_image( $image_id, $this->suggested_image_square );

		$this->image_utility->expects( $this->exactly( 2 ) )
		->method( 'recommend_size' )
		->willReturn( false );

		// It should not create a subsize because the original image already has the suggested size.
		$this->image_utility->expects( $this->exactly( 0 ) )
		->method( 'maybe_add_subsize_image' );

		$this->wp->expects( $this->exactly( 1 ) )
		->method( 'get_posts' )
		->willReturn( [ $image_id ] );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [];
		$images[ self::MARKETING_IMAGE_KEY ]        = [];

		$this->assertEquals( $this->format_post_asset_response( $this->post, $images ), $this->asset_suggestions->get_assets_suggestions( $this->post->ID, 'post' ) );

	}



}
