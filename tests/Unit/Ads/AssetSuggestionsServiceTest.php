<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ArrayUtil;

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

	protected const DEFAULT_PER_PAGE                    = 30;
	protected const DEFAULT_PER_PAGE_POSTS              = 15;
	protected const EMPTY_SEARCH                        = '';
	protected const TEST_SEARCH                         = 'mySearch';
	protected const DEFAULT_MAXIMUM_MARKETING_IMAGES    = 20;
	protected const INVALID_ID                          = 123456;
	protected const SQUARE_MARKETING_IMAGE_KEY          = 'gla_square_marketing';
	protected const MARKETING_IMAGE_KEY                 = 'gla_marketing';
	protected const MARKETING_SQUARE_IMAGE_MINIMUM_SIZE = [ 300, 300 ];
	protected const MARKETING_IMAGE_MINIMUM_SIZE        = [ 600, 314 ];

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
		$this->wc                = $this->createMock( WC::class );
		$this->asset_suggestions = new AssetSuggestionsService( $this->wp, $this->wc );

		$this->post = $this->factory()->post->create_and_get( [ 'post_title' => 'Abcd' ] );
		$this->term = $this->factory()->term->create_and_get( [ 'name' => 'bcde' ] );

		$this->suggested_post = $this->format_url_post_item( $this->post );
		$this->suggested_term = $this->format_url_term_item( $this->term );

		$this->minimum_image_requirements[ self::MARKETING_IMAGE_KEY ]        = self::MARKETING_IMAGE_MINIMUM_SIZE;
		$this->minimum_image_requirements[ self::SQUARE_MARKETING_IMAGE_KEY ] = self::MARKETING_SQUARE_IMAGE_MINIMUM_SIZE;

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

	protected function format_post_asset_response( $post, $marketing_images = [] ) {
		return [
			'final_url'               => get_permalink( $post->ID ),
			'headline'                => [ $post->post_title ],
			'long_headline'           => [ get_bloginfo( 'name' ) . ': ' . $post->post_title ],
			'description'             => ArrayUtil::remove_empty_values( [ $post->post_excerpt, get_bloginfo( 'description' ) ] ),
			'business_name'           => get_bloginfo( 'name' ),
			'display_url_path'        => [ $post->post_name ],
			'logo'                    => [],
			'square_marketing_images' => $marketing_images[ self::SQUARE_MARKETING_IMAGE_KEY ] ?? [],
			'marketing_images'        => $marketing_images[ self::MARKETING_IMAGE_KEY ] ?? [],
			'call_to_action'          => null,
		];

	}

	protected function format_term_asset_response( $term, $marketing_images = [] ) {
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

	protected function update_size_image( $attachmend_id, $size, $size_type = null ) {
		$metadata           = wp_get_attachment_metadata( $attachmend_id );
		$metadata['width']  = $size[0];
		$metadata['height'] = $size[1];

		if ( $size_type ) {
			$metadata['sizes'][ $size_type ] = [
				'file'   => $metadata['file'],
				'width'  => $this->minimum_image_requirements[ $size_type ][0],
				'height' => $this->minimum_image_requirements[ $size_type ][1],
			];
		}

		wp_update_attachment_metadata( $attachmend_id, $metadata );
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
		$image_id = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ), $this->post->ID );

		$this->update_size_image( $image_id, [ 300, 300 ], self::SQUARE_MARKETING_IMAGE_KEY );

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

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_id, self::SQUARE_MARKETING_IMAGE_KEY ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [];

		$this->assertEquals( $this->format_post_asset_response( $this->post, $images ), $this->asset_suggestions->get_assets_suggestions( $this->post->ID, 'post' ) );
	}

	public function test_get_post_assets_for_products() {
		$post     = $this->factory()->post->create_and_get( [ 'post_type' => 'product' ] );
		$image_id = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );

		$this->update_size_image( $image_id, [ 1200, 1200 ], self::SQUARE_MARKETING_IMAGE_KEY );
		$this->update_size_image( $image_id, [ 1200, 1200 ], self::MARKETING_IMAGE_KEY );

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
		$image_post_id    = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );
		$image_product_id = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );

		$this->update_size_image( $image_post_id, [ 1200, 1200 ], self::SQUARE_MARKETING_IMAGE_KEY );
		$this->update_size_image( $image_product_id, [ 1200, 1200 ], self::MARKETING_IMAGE_KEY );

		update_option( 'woocommerce_shop_page_id', $this->post->ID );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_gallery_image_ids( [ $image_product_id ] );

		$this->wp->expects( $this->exactly( 3 ) )
			->method( 'get_posts' )
			->willReturnOnConsecutiveCalls( [ $image_post_id ], [ $product->get_id() ], [ $image_product_id ] );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_post_id, self::SQUARE_MARKETING_IMAGE_KEY ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [ wp_get_attachment_image_url( $image_product_id, self::MARKETING_IMAGE_KEY ) ];

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
		$image_post_1 = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );
		$image_post_2 = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );

		$this->update_size_image( $image_post_1, [ 1200, 1200 ], self::SQUARE_MARKETING_IMAGE_KEY );
		$this->update_size_image( $image_post_2, [ 1200, 1200 ], self::SQUARE_MARKETING_IMAGE_KEY );

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

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_post_1, self::SQUARE_MARKETING_IMAGE_KEY ), wp_get_attachment_image_url( $image_post_2, self::SQUARE_MARKETING_IMAGE_KEY ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [];

		$this->assertEquals( $this->format_term_asset_response( $this->term, $images ), $this->asset_suggestions->get_assets_suggestions( $this->term->term_id, 'term' ) );

	}

	public function test_get_term_without_product() {
		$post             = $this->factory()->post->create_and_get();
		$image_post_1     = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );
		$image_post_2     = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );
		$marketing_images = [ wp_get_attachment_image_url( $image_post_1 ), wp_get_attachment_image_url( $image_post_2 ) ];

		$posts_ids_assigned_to_term = [ $this->post, $post ];

		$this->update_size_image( $image_post_1, [ 1200, 1200 ], self::SQUARE_MARKETING_IMAGE_KEY );
		$this->update_size_image( $image_post_2, [ 1200, 1200 ], self::MARKETING_IMAGE_KEY );

		$this->wc->expects( $this->never() )
			->method( 'maybe_get_product' );

		$this->wp->expects( $this->exactly( 2 ) )
			->method( 'get_posts' )
			->willReturnOnConsecutiveCalls( $posts_ids_assigned_to_term, [ $image_post_1, $image_post_2 ] );

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_post_1, self::SQUARE_MARKETING_IMAGE_KEY ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [ wp_get_attachment_image_url( $image_post_2, self::MARKETING_IMAGE_KEY ) ];

		$this->assertEquals( $this->format_term_asset_response( $this->term, $images ), $this->asset_suggestions->get_assets_suggestions( $this->term->term_id, 'term' ) );

	}

	public function test_get_term_without_assigned_posts() {
		$posts_ids_assigned_to_term = [];

		$this->wc->expects( $this->never() )
			->method( 'maybe_get_product' );

		$this->wp->expects( $this->exactly( 1 ) )
			->method( 'get_posts' )
			->willReturnOnConsecutiveCalls( $posts_ids_assigned_to_term );

		$this->assertEquals( $this->format_term_asset_response( $this->term, [] ), $this->asset_suggestions->get_assets_suggestions( $this->term->term_id, 'term' ) );

	}

	public function test_get_invalid_term_id() {
		$this->expectException( Exception::class );
		$this->asset_suggestions->get_assets_suggestions( self::INVALID_ID, 'term' );
	}


	public function test_resize_image() {
		$image_id = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ), $this->post->ID );

		$this->update_size_image( $image_id, [ 300, 300 ] );

		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->willReturn( [ $image_id ] );

		// As the file test-image-1.png is only 64x64 we tweak the code, so it seems that the image has been resize to 300x300 px.
		add_filter(
			'wp_generate_attachment_metadata',
			function ( $metadata ) use ( $image_id ) {
				$metadata['sizes'][ self::SQUARE_MARKETING_IMAGE_KEY ] = [
					'file'   => basename( get_attached_file( $image_id ) ),
					'width'  => $this->minimum_image_requirements[ self::SQUARE_MARKETING_IMAGE_KEY ][0],
					'height' => $this->minimum_image_requirements[ self::SQUARE_MARKETING_IMAGE_KEY ][1],
				];

				return $metadata;
			}
		);

		$images[ self::SQUARE_MARKETING_IMAGE_KEY ] = [ wp_get_attachment_image_url( $image_id, self::SQUARE_MARKETING_IMAGE_KEY ) ];
		$images[ self::MARKETING_IMAGE_KEY ]        = [];

		$this->assertEquals( $this->format_post_asset_response( $this->post, $images ), $this->asset_suggestions->get_assets_suggestions( $this->post->ID, 'post' ) );
	}

}
