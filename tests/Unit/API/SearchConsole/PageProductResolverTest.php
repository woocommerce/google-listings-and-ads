<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\PageProductResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class PageProductResolverTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class PageProductResolverTest extends UnitTest {

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var PageProductResolver $resolver */
	protected $resolver;

	public function setUp(): void {
		parent::setUp();

		$this->set_permalink_structure( '/%postname%/' );
		register_post_type( 'product', [ 'public' => true ] );
		flush_rewrite_rules();

		$this->product_helper = $this->createMock( ProductHelper::class );
		$this->resolver       = new PageProductResolver( $this->product_helper );
	}

	protected function create_product_post( string $slug ): int {
		return $this->factory()->post->create(
			[
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_name'   => $slug,
			]
		);
	}

	public function test_resolves_a_product_url_to_its_product_id() {
		$product_id = $this->create_product_post( 'blue-shirt' );

		$this->product_helper->method( 'maybe_swap_for_parent_id' )
			->with( $product_id )
			->willReturn( $product_id );

		$this->assertSame( $product_id, $this->resolver->resolve_product_id( get_permalink( $product_id ) ) );
	}

	public function test_resolves_a_variation_url_to_the_parent_product() {
		$variation_id = $this->create_product_post( 'blue-shirt-large' );
		$parent_id    = $this->create_product_post( 'blue-shirt' );

		$this->product_helper->method( 'maybe_swap_for_parent_id' )
			->with( $variation_id )
			->willReturn( $parent_id );

		$this->assertSame( $parent_id, $this->resolver->resolve_product_id( get_permalink( $variation_id ) ) );
	}

	public function test_resolves_a_url_with_a_changed_slug_to_the_current_product() {
		$product_id = $this->create_product_post( 'new-slug' );
		add_post_meta( $product_id, '_wp_old_slug', 'old-slug' );

		$this->product_helper->method( 'maybe_swap_for_parent_id' )
			->with( $product_id )
			->willReturn( $product_id );

		$this->assertSame( $product_id, $this->resolver->resolve_product_id( home_url( '/product/old-slug/' ) ) );
	}

	public function test_returns_null_for_a_url_that_matches_nothing() {
		$this->product_helper->expects( $this->never() )->method( 'maybe_swap_for_parent_id' );

		$this->assertNull( $this->resolver->resolve_product_id( home_url( '/product/never-existed/' ) ) );
	}

	public function test_returns_null_when_the_matched_post_is_not_a_product() {
		$page_id = $this->factory()->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'about',
			]
		);

		$this->product_helper->method( 'maybe_swap_for_parent_id' )
			->with( $page_id )
			->willThrowException( new InvalidValue( 'Not a product' ) );

		$this->assertNull( $this->resolver->resolve_product_id( get_permalink( $page_id ) ) );
	}

	public function test_returns_null_for_a_url_on_another_host_even_when_the_slug_matches() {
		$product_id = $this->create_product_post( 'new-slug' );
		add_post_meta( $product_id, '_wp_old_slug', 'old-slug' );

		$this->product_helper->expects( $this->never() )->method( 'maybe_swap_for_parent_id' );

		$this->assertNull( $this->resolver->resolve_product_id( 'https://other.example/product/old-slug/' ) );
	}

	public function test_ignores_a_leading_www_when_comparing_hosts() {
		$product_id = $this->create_product_post( 'new-slug' );
		add_post_meta( $product_id, '_wp_old_slug', 'old-slug' );

		$this->product_helper->method( 'maybe_swap_for_parent_id' )->willReturn( $product_id );

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$url  = 'https://www.' . preg_replace( '/^www\./', '', $host ) . '/product/old-slug/';

		$this->assertSame( $product_id, $this->resolver->resolve_product_id( $url ) );
	}
}
