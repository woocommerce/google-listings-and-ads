<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\PagePostResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WC_Helper_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class PagePostResolverTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class PagePostResolverTest extends UnitTest {

	/** @var PagePostResolver $resolver */
	protected $resolver;

	public function setUp(): void {
		parent::setUp();

		$this->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$this->resolver = new PagePostResolver();
	}

	protected function create_page( string $slug ): int {
		return $this->factory()->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => $slug,
			]
		);
	}

	public function test_resolves_a_page_url_to_its_post_id() {
		$page_id = $this->create_page( 'about' );

		$this->assertSame( $page_id, $this->resolver->resolve_post_id( get_permalink( $page_id ), [ 'page' ] ) );
	}

	public function test_resolves_a_url_with_a_changed_slug_within_the_given_post_types() {
		$post_id = $this->factory()->post->create(
			[
				'post_status' => 'publish',
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

		$this->assertSame( $post_id, $this->resolver->resolve_post_id( $old_url, [ 'post' ] ) );
		$this->assertSame( 0, $this->resolver->resolve_post_id( $old_url, [ 'page' ] ) );
	}

	public function test_does_not_match_an_archive_url_that_shares_a_former_slug() {
		$post_id = $this->factory()->post->create(
			[
				'post_status' => 'publish',
				'post_name'   => 'news',
			]
		);

		wp_update_post(
			[
				'ID'        => $post_id,
				'post_name' => 'latest-news',
			]
		);

		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/category/news/' ), [ 'post' ] ) );
	}

	public function test_resolves_plain_permalink_queries_by_slug() {
		$this->set_permalink_structure( '' );

		$page_id = $this->create_page( 'about' );
		$post_id = $this->factory()->post->create(
			[
				'post_status' => 'publish',
				'post_name'   => 'spring-sale',
			]
		);
		$product = WC_Helper_Product::create_simple_product();
		$slug    = get_post_field( 'post_name', $product->get_id() );

		$this->assertSame( $page_id, $this->resolver->resolve_post_id( home_url( '/?pagename=about' ), [] ) );
		$this->assertSame( $post_id, $this->resolver->resolve_post_id( home_url( '/?name=spring-sale' ), [] ) );
		$this->assertSame( $product->get_id(), $this->resolver->resolve_post_id( home_url( '/?product=' . $slug ), [] ) );
		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?product_cat=clothing' ), [] ) );
	}

	public function test_does_not_resolve_a_plain_permalink_query_for_a_draft() {
		$this->set_permalink_structure( '' );

		$this->factory()->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_name'   => 'coming-soon',
			]
		);

		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?pagename=coming-soon' ), [] ) );
	}

	public function test_resolves_a_query_url_naming_a_former_slug_within_the_given_post_types() {
		$post_id = $this->factory()->post->create(
			[
				'post_status' => 'publish',
				'post_name'   => 'old-news',
			]
		);

		wp_update_post(
			[
				'ID'        => $post_id,
				'post_name' => 'latest-news',
			]
		);

		$this->assertSame( $post_id, $this->resolver->resolve_post_id( home_url( '/?name=old-news' ), [ 'post' ] ) );
		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?name=old-news' ), [ 'page' ] ) );
	}

	public function test_returns_zero_for_a_url_that_matches_nothing() {
		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/never-existed/' ), [ 'page' ] ) );
	}

	public function test_returns_zero_for_a_url_on_another_host() {
		$this->create_page( 'about' );

		$this->assertSame( 0, $this->resolver->resolve_post_id( 'https://other.example/about/', [ 'page' ] ) );
	}

	public function test_recognizes_store_urls_ignoring_a_leading_www() {
		$this->assertTrue( $this->resolver->is_store_url( home_url( '/shop/' ) ) );
		$this->assertTrue( $this->resolver->is_store_url( str_replace( '://', '://www.', home_url( '/shop/' ) ) ) );
		$this->assertFalse( $this->resolver->is_store_url( 'https://other.example/shop/' ) );
		$this->assertFalse( $this->resolver->is_store_url( 'not a url' ) );
	}
}
