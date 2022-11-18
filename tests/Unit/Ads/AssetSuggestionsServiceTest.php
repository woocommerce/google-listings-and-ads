<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 *
 * @property MockObject|WP  $wp
 */
class AssetSuggestionsServiceTest extends UnitTest {

	protected const TEST_POST_TYPES = [
		'post', 'page', 'product', 'attachment'
	];
	protected const TEST_POST_TYPES_NO_ATTACHMENT = [
		'post', 'page', 'product'
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp              = $this->createMock( WP::class );
		$this->asset_suggestions = new AssetSuggestionsService ( $this->wp );

		$this->post = $this->factory()->post->create_and_get();
		$this->term = $this->factory()->term->create_and_get();

		$this->suggested_post = [
			'id' => $this->post->ID,
			'type' => 'post',
			'post_type' => $this->post->post_type,
			'title' => $this->post->post_title,
			'url' => get_permalink( $this->post->ID )			
		];

		$this->suggested_term = [
			'id' => $this->term->term_id,
			'type' => 'term',
			'post_type' => null,
			'title' => $this->term->name,
			'url' => get_term_link( $this->term->term_id, $this->term->taxonomy )			
		];		

	}

	public function test_get_post_suggestions() {

		$this->wp->expects( $this->once() )
			->method( 'get_post_types' )
			->willReturn( self::TEST_POST_TYPES );
		
		//Should be called without the attachment type
		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->with( [
				'post_type' => self::TEST_POST_TYPES_NO_ATTACHMENT,
				'posts_per_page' => -1,
				'post_status'    => 'publish',				
			])
			->willReturn( [ $this->post ] );

			$this->wp->expects( $this->once() )
			->method( 'get_taxonomies' );
			
			$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->willReturn( []);			

		$this->assertEquals( [ $this->suggested_post ], $this->asset_suggestions->get_pages_suggestions() );
	}

	public function test_get_term_suggestions() {

		$this->wp->expects( $this->once() )
			->method( 'get_post_types' )
			->willReturn( self::TEST_POST_TYPES );
		
		//Should be called without the attachment type
		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->willReturn( [] );

			$this->wp->expects( $this->once() )
			->method( 'get_taxonomies' );
			
			$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->willReturn( [ $this-> term ]);			

		$this->assertEquals( [ $this->suggested_term ], $this->asset_suggestions->get_pages_suggestions() );
	}
	
	public function test_get_all_suggestions() {

		$this->wp->expects( $this->once() )
			->method( 'get_post_types' )
			->willReturn( self::TEST_POST_TYPES );
		
		//Should be called without the attachment type
		$this->wp->expects( $this->once() )
			->method( 'get_posts' )
			->willReturn( [ $this->post ] );

			$this->wp->expects( $this->once() )
			->method( 'get_taxonomies' );
			
			$this->wp->expects( $this->once() )
			->method( 'get_terms' )
			->willReturn( [ $this-> term ]);			

		$this->assertEquals( [ $this->suggested_post, $this->suggested_term ], $this->asset_suggestions->get_pages_suggestions() );
	}
}
