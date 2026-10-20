<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\PageProductResolver;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use WC_Helper_Product;
use WC_Post_Types;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class PageProductResolverTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\SearchConsole
 */
class PageProductResolverTest extends ContainerAwareUnitTest {

	/** @var PageProductResolver $resolver */
	protected $resolver;

	public function setUp(): void {
		parent::setUp();

		// A post type only gets its rewrite rules when it is registered while pretty permalinks are on,
		// and WooCommerce registers its product type earlier, so register it again for each test.
		$this->set_permalink_structure( '/%postname%/' );
		register_post_type(
			'product',
			[
				'public'  => true,
				'rewrite' => [ 'slug' => 'product' ],
			]
		);
		flush_rewrite_rules();

		$this->resolver = new PageProductResolver( $this->container->get( ProductHelper::class ) );
	}

	public function tearDown(): void {
		unregister_post_type( 'product' );
		WC_Post_Types::register_post_types();
		flush_rewrite_rules();

		parent::tearDown();
	}

	protected function product_url( WC_Product $product ): string {
		return home_url( '/product/' . get_post_field( 'post_name', $product->get_id() ) . '/' );
	}

	public function test_resolves_a_product_url_to_its_product_id() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertSame( $product->get_id(), $this->resolver->resolve_product_id( $this->product_url( $product ) ) );
	}

	public function test_resolves_a_variation_url_to_the_parent_product() {
		$parent = WC_Helper_Product::create_variation_product();

		$this->assertSame( $parent->get_id(), $this->resolver->resolve_product_id( $this->product_url( $parent ) . '?attribute_pa_color=blue' ) );
	}

	public function test_resolves_a_url_that_points_straight_at_a_variation_to_the_parent_product() {
		$parent       = WC_Helper_Product::create_variation_product();
		$variation_id = $parent->get_children()[0];

		$this->assertSame( $parent->get_id(), $this->resolver->resolve_product_id( home_url( '/?p=' . $variation_id ) ) );
	}

	public function test_resolves_a_url_with_a_changed_slug_to_the_current_product() {
		$product  = WC_Helper_Product::create_simple_product();
		$old_url  = $this->product_url( $product );
		$old_slug = get_post_field( 'post_name', $product->get_id() );

		wp_update_post(
			[
				'ID'        => $product->get_id(),
				'post_name' => 'renamed-' . $old_slug,
			]
		);

		$this->assertSame( $product->get_id(), $this->resolver->resolve_product_id( $old_url ) );
	}

	public function test_resolves_a_private_product_whose_slug_has_changed() {
		$this->login_as_administrator();

		$product  = WC_Helper_Product::create_simple_product();
		$old_url  = $this->product_url( $product );
		$old_slug = get_post_field( 'post_name', $product->get_id() );

		wp_update_post(
			[
				'ID'          => $product->get_id(),
				'post_status' => 'private',
				'post_name'   => 'renamed-' . $old_slug,
			]
		);

		$this->assertSame( $product->get_id(), $this->resolver->resolve_product_id( $old_url ) );
	}

	public function test_resolves_a_slug_shared_by_several_former_products_to_the_most_recently_published() {
		$older = WC_Helper_Product::create_simple_product();
		$newer = WC_Helper_Product::create_simple_product();

		foreach ( [
			$older->get_id() => '2020-01-01 00:00:00',
			$newer->get_id() => '2024-01-01 00:00:00',
		] as $product_id => $date ) {
			wp_update_post(
				[
					'ID'            => $product_id,
					'post_date'     => $date,
					'post_date_gmt' => $date,
				]
			);
			add_post_meta( $product_id, '_wp_old_slug', 'shared-former-slug' );
		}

		$this->assertSame( $newer->get_id(), $this->resolver->resolve_product_id( home_url( '/product/shared-former-slug/' ) ) );
	}

	public function test_returns_null_for_a_url_that_matches_nothing() {
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

		$this->assertNull( $this->resolver->resolve_product_id( get_permalink( $page_id ) ) );
	}

	public function test_returns_null_for_a_url_on_another_host_even_when_the_slug_matches() {
		$product  = WC_Helper_Product::create_simple_product();
		$old_slug = get_post_field( 'post_name', $product->get_id() );

		wp_update_post(
			[
				'ID'        => $product->get_id(),
				'post_name' => 'renamed-' . $old_slug,
			]
		);

		$this->assertNull( $this->resolver->resolve_product_id( 'https://other.example/product/' . $old_slug . '/' ) );
	}

	public function test_ignores_a_leading_www_when_comparing_hosts() {
		$product = WC_Helper_Product::create_simple_product();
		$url     = str_replace( '://', '://www.', $this->product_url( $product ) );

		$this->assertSame( $product->get_id(), $this->resolver->resolve_product_id( $url ) );
	}

	public function test_compares_hosts_against_the_filtered_site_url() {
		$product = WC_Helper_Product::create_simple_product();
		$slug    = get_post_field( 'post_name', $product->get_id() );

		add_filter(
			'woocommerce_gla_site_url',
			function () {
				return 'https://canonical.example';
			}
		);

		$this->assertNull( $this->resolver->resolve_product_id( home_url( '/product/' . $slug . '/' ) ) );

		remove_all_filters( 'woocommerce_gla_site_url' );
	}
}
