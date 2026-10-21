<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Admin;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use ReflectionClass;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

class AdminProductUrlBaseTest extends UnitTest {

	/**
	 * Remove the permalink structure set by a test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_permalinks' );

		parent::tearDown();
	}

	/**
	 * Get the product URL base of a store with the given product permalink structure.
	 *
	 * @param string $product_base The product permalink structure.
	 *
	 * @return string
	 */
	protected function get_product_url_base( string $product_base ): string {
		update_option( 'woocommerce_permalinks', [ 'product_base' => $product_base ] );

		$admin  = ( new ReflectionClass( Admin::class ) )->newInstanceWithoutConstructor();
		$method = new ReflectionMethod( Admin::class, 'get_product_url_base' );
		$method->setAccessible( true );

		return $method->invoke( $admin );
	}

	public function test_returns_the_product_base_wrapped_in_slashes(): void {
		$this->assertSame( '/product/', $this->get_product_url_base( '/product' ) );
	}

	public function test_leaves_out_the_placeholders_of_the_structure(): void {
		$this->assertSame( '/shop/', $this->get_product_url_base( '/shop/%product_cat%' ) );
	}

	public function test_returns_an_empty_string_when_the_structure_starts_with_a_placeholder(): void {
		$this->assertSame( '', $this->get_product_url_base( '/%product_cat%' ) );
	}
}
