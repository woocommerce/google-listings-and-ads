<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductFilter;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use WC_Helper_Product;

/**
 * Class ProductFilterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class ProductFilterTest extends ContainerAwareUnitTest {

	public function setUp() {
		parent::setUp();

		remove_all_filters( 'woocommerce_gla_get_sync_ready_products_pre_filter' );
		remove_all_filters( 'woocommerce_gla_get_sync_ready_products_filter' );
	}

	public function test_filter_sync_ready_products_with_no_filters() {
		$product_helper = $this->createMock( ProductHelper::class );

		$product_a = WC_Helper_Product::create_simple_product();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_c = WC_Helper_Product::create_simple_product();

		$product_helper->expects( $this->exactly( 3 ) )
			->method( 'is_sync_ready' )
			->withConsecutive( [ $product_a ], [ $product_b ], [ $product_c ] )
			->willReturnOnConsecutiveCalls( false, true, false );

		$product_helper->expects( $this->once() )
			->method( 'is_sync_failed_recently' )
			->with( $product_b )
			->willReturn( false );

		$product_filter = new ProductFilter( $product_helper );

		$filtered_products = $product_filter->filter_sync_ready_products( [ $product_a, $product_b, $product_c ] );

		$this->assertEquals( [ $product_b ], $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}

	public function test_filter_sync_ready_products_with_pre_filter() {
		$product_helper = $this->createMock( ProductHelper::class );

		add_filter(
			'woocommerce_gla_get_sync_ready_products_pre_filter',
			function ( $products ) {
				return [];
			}
		);

		$product_a = WC_Helper_Product::create_simple_product();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_c = WC_Helper_Product::create_simple_product();

		$product_helper->expects( $this->never() )->method( 'is_sync_ready' );
		$product_helper->expects( $this->never() )->method( 'is_sync_failed_recently' );

		$product_filter = new ProductFilter( $product_helper );

		$filtered_products = $product_filter->filter_sync_ready_products( [ $product_a, $product_b, $product_c ] );

		$this->assertEmpty( $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}

	public function test_filter_sync_ready_products_with_post_filter() {
		$product_helper = $this->createMock( ProductHelper::class );

		add_filter(
			'woocommerce_gla_get_sync_ready_products_filter',
			function ( $products ) {
				return [];
			}
		);

		$product_a = WC_Helper_Product::create_simple_product();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_c = WC_Helper_Product::create_simple_product();

		$product_helper->expects( $this->exactly( 3 ) )
			->method( 'is_sync_ready' )
			->withConsecutive( [ $product_a ], [ $product_b ], [ $product_c ] )
			->willReturnOnConsecutiveCalls( false, true, false );

		$product_helper->expects( $this->once() )
			->method( 'is_sync_failed_recently' )
			->with( $product_b )
			->willReturn( false );

		$product_filter = new ProductFilter( $product_helper );

		$filtered_products = $product_filter->filter_sync_ready_products( [ $product_a, $product_b, $product_c ] );

		$this->assertEmpty( $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}

	public function test_products_for_delete() {
		$product_helper = $this->createMock( ProductHelper::class );

		$product_a = WC_Helper_Product::create_simple_product();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_c = WC_Helper_Product::create_simple_product();

		$product_helper->expects( $this->exactly( 3 ) )
			->method( 'is_delete_failed_threshold_reached' )
			->withConsecutive( [ $product_a ], [ $product_b ], [ $product_c ] )
			->willReturnOnConsecutiveCalls( false, true, false );

		$product_filter = new ProductFilter( $product_helper );

		$filtered_products = $product_filter->filter_products_for_delete( [ $product_a, $product_b, $product_c ] );

		$this->assertEquals( [ $product_a, $product_c ], $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}
}
