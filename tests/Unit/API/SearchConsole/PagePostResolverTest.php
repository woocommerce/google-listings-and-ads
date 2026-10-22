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

	public function test_returns_zero_for_an_id_query_naming_a_draft_or_trashed_post() {
		$draft_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );
		$page_id  = $this->create_page( 'old-offer' );
		wp_trash_post( $page_id );

		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?p=' . $draft_id ), [ 'post' ] ) );
		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?page_id=' . $page_id ), [ 'page' ] ) );
	}

	public function test_returns_zero_for_an_id_query_naming_a_revision() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => 'Updated content',
			]
		);
		$revision_ids = array_keys( wp_get_post_revisions( $post_id ) );

		$this->assertNotEmpty( $revision_ids );
		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?p=' . $revision_ids[0] ), [ 'post' ] ) );
	}

	public function test_resolves_an_attachment_page_by_its_id() {
		$attachment_id = $this->factory()->attachment->create( [ 'post_status' => 'inherit' ] );

		$this->assertSame( $attachment_id, $this->resolver->resolve_post_id( home_url( '/?attachment_id=' . $attachment_id ), [] ) );
	}

	public function test_resolves_query_urls_on_a_store_with_a_static_front_page() {
		$front_id = $this->create_page( 'home' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );

		$product = WC_Helper_Product::create_simple_product();
		$slug    = get_post_field( 'post_name', $product->get_id() );

		$this->assertSame( $product->get_id(), $this->resolver->resolve_post_id( home_url( '/?product=' . $slug ), [ 'product' ] ) );
		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?product_cat=shoes' ), [ 'product' ] ) );
		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?s=shoes' ), [ 'page' ] ) );
		$this->assertSame( $front_id, $this->resolver->resolve_post_id( home_url( '/' ), [ 'page' ] ) );
		$this->assertSame( $front_id, $this->resolver->resolve_post_id( home_url( '/?utm_source=google' ), [ 'page' ] ) );
	}

	public function test_does_not_match_a_post_type_archive_to_a_former_slug() {
		register_post_type(
			'gla_event',
			[
				'public'      => true,
				'has_archive' => 'events',
				'rewrite'     => [ 'slug' => 'event' ],
			]
		);
		flush_rewrite_rules();

		$post_id = $this->factory()->post->create(
			[
				'post_status' => 'publish',
				'post_name'   => 'events',
			]
		);
		wp_update_post(
			[
				'ID'        => $post_id,
				'post_name' => 'events-2026',
			]
		);

		$result = $this->resolver->resolve_post_id( home_url( '/events/' ), [ 'post' ] );

		unregister_post_type( 'gla_event' );

		$this->assertSame( 0, $result );
	}

	public function test_returns_zero_for_an_id_query_naming_a_post_type_that_is_not_viewable() {
		$block_id = $this->factory()->post->create(
			[
				'post_type'   => 'wp_block',
				'post_status' => 'publish',
			]
		);

		$this->assertSame( 0, $this->resolver->resolve_post_id( home_url( '/?p=' . $block_id ), [] ) );
	}

	public function test_only_recognizes_urls_under_the_store_path_as_store_urls() {
		add_filter(
			'woocommerce_gla_site_url',
			function () {
				return home_url( '/store' );
			}
		);

		$this->assertTrue( $this->resolver->is_store_url( home_url( '/store' ) ) );
		$this->assertTrue( $this->resolver->is_store_url( home_url( '/store/about/' ) ) );
		$this->assertFalse( $this->resolver->is_store_url( home_url( '/' ) ) );
		$this->assertFalse( $this->resolver->is_store_url( home_url( '/blog/x/' ) ) );
		$this->assertFalse( $this->resolver->is_store_url( home_url( '/storefront/' ) ) );

		remove_all_filters( 'woocommerce_gla_site_url' );
	}

	public function test_resolves_a_url_on_the_filtered_site_url() {
		$page_id = $this->create_page( 'about' );
		add_filter(
			'woocommerce_gla_site_url',
			function () {
				return 'https://canonical.example/store';
			}
		);

		$result = $this->resolver->resolve_post_id( 'https://www.canonical.example/store/about/?utm_source=google', [ 'page' ] );

		remove_all_filters( 'woocommerce_gla_site_url' );

		$this->assertSame( $page_id, $result );
	}

	public function test_keeps_a_url_already_under_home_when_the_site_url_is_filtered_to_another_path() {
		$home    = untrailingslashit( home_url() );
		$page_id = $this->create_page( 'about' );

		$filter_home = function () use ( $home ) {
			return $home . '/wp';
		};
		add_filter( 'pre_option_home', $filter_home );
		add_filter(
			'woocommerce_gla_site_url',
			function () use ( $home ) {
				return $home;
			}
		);

		$result = $this->resolver->resolve_post_id( $home . '/wp/about/', [ 'page' ] );

		remove_filter( 'pre_option_home', $filter_home );
		remove_all_filters( 'woocommerce_gla_site_url' );

		$this->assertSame( $page_id, $result );
	}

	public function test_resolves_the_posts_page() {
		$posts_page_id = $this->create_page( 'blog' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->create_page( 'home' ) );
		update_option( 'page_for_posts', $posts_page_id );

		$this->assertSame( $posts_page_id, $this->resolver->resolve_post_id( home_url( '/blog/' ), [ 'page' ] ) );
		$this->assertSame( $posts_page_id, $this->resolver->resolve_post_id( home_url( '/blog/?utm_source=google' ), [ 'page' ] ) );
	}

	public function test_resolves_an_attachment_page_by_its_slug_query() {
		$attachment_id = $this->factory()->attachment->create(
			[
				'post_status' => 'inherit',
				'post_name'   => 'blue-shirt-photo',
			]
		);

		$this->assertSame( $attachment_id, $this->resolver->resolve_post_id( home_url( '/?attachment=blue-shirt-photo' ), [] ) );
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
