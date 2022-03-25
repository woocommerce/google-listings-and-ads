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

	private $product_helper;

	private $product_filter;

	private $products = [];

	public function setUp() {

		parent::setUp();

		$this->products = [
			WC_Helper_Product::create_simple_product(),
			WC_Helper_Product::create_simple_product(),
			WC_Helper_Product::create_simple_product(),
		];

		$this->product_helper = $this->createMock( ProductHelper::class );

		$this->product_filter = new ProductFilter( $this->product_helper );

		remove_all_filters( 'woocommerce_gla_get_sync_ready_products_pre_filter' );
		remove_all_filters( 'woocommerce_gla_get_sync_ready_products_filter' );
	}

	public function test_filter_sync_ready_products_with_no_filters() {

		[ $product_a, $product_b, $product_c ] = $this->products;

		$this->product_helper->expects( $this->exactly( 3 ) )
			->method( 'is_sync_ready' )
			->withConsecutive( [ $product_a ], [ $product_b ], [ $product_c ] )
			->willReturnOnConsecutiveCalls( false, true, false );

		$this->product_helper->expects( $this->once() )
			->method( 'is_sync_failed_recently' )
			->with( $product_b )
			->willReturn( false );

		$filtered_products = $this->product_filter->filter_sync_ready_products( $this->products );

		$this->assertEquals( [ $product_b ], $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}

	public function test_filter_sync_ready_products_with_pre_filter() {

		add_filter(
			'woocommerce_gla_get_sync_ready_products_pre_filter',
			function ( $products ) {
				return [];
			}
		);

		$this->product_helper->expects( $this->never() )->method( 'is_sync_ready' );
		$this->product_helper->expects( $this->never() )->method( 'is_sync_failed_recently' );

		$filtered_products = $this->product_filter->filter_sync_ready_products( $this->products );

		$this->assertEmpty( $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}

	public function test_filter_sync_ready_products_with_post_filter() {

		add_filter(
			'woocommerce_gla_get_sync_ready_products_filter',
			function ( $products ) {
				return [];
			}
		);

		[ $product_a, $product_b, $product_c ] = $this->products;

		$this->product_helper->expects( $this->exactly( 3 ) )
			->method( 'is_sync_ready' )
			->withConsecutive( [ $product_a ], [ $product_b ], [ $product_c ] )
			->willReturnOnConsecutiveCalls( false, true, false );

		$this->product_helper->expects( $this->once() )
			->method( 'is_sync_failed_recently' )
			->with( $product_b )
			->willReturn( false );

		$filtered_products = $this->product_filter->filter_sync_ready_products( $this->products );

		$this->assertEmpty( $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}

	public function test_products_for_delete() {

		[ $product_a, $product_b, $product_c ] = $this->products;

		$this->product_helper->expects( $this->exactly( 3 ) )
			->method( 'is_delete_failed_threshold_reached' )
			->withConsecutive( [ $product_a ], [ $product_b ], [ $product_c ] )
			->willReturnOnConsecutiveCalls( false, true, false );

		$filtered_products = $this->product_filter->filter_products_for_delete( $this->products );

		$this->assertEquals( [ $product_a, $product_c ], $filtered_products->get() );
		$this->assertEquals( 3, $filtered_products->get_unfiltered_count() );
	}
}
