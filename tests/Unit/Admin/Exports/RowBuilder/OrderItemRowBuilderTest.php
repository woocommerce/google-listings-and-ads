<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Exports\RowBuilder;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\RowBuilder\OrderItemRowBuilder;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Helper_Order;
use WC_Helper_Coupon;
use WC_Order_Item_Product;
use WC_Order_Item_Coupon;

/**
 * Class OrderItemRowBuilderTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Exports\RowBuilder
 */
class OrderItemRowBuilderTest extends UnitTest {

	protected const TEST_WCS_MCA_ID = 987654321;

	/** @var OrderItemRowBuilder $builder */
	protected $builder;

	/** @var MockObject|Middleware $middleware */
	protected $middleware;

	public function setUp(): void {
		parent::setUp();

		$this->middleware = $this->createMock( Middleware::class );

		$this->builder = new OrderItemRowBuilder( $this->middleware );
	}

	public function test_returns_null_if_instance_not_wc_order_item() {
		$row = $this->builder->build_row( [] );

		$this->assertNull( $row );
	}

	public function test_builds_correct_row_for_order_item() {
		// Create a test product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 40 );
		$product->save();

		// Create a test order.
		$order = WC_Helper_Order::create_order();

		// Remove the auto-added default item
		foreach ( $order->get_items() as $existing_item ) {
			$order->remove_item( $existing_item->get_id() );
		}

		// Add test product to the test order.
		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 2 );
		$item->set_total( 80 );
		$item->set_subtotal( 100 );
		$order->add_item( $item );

		// Add a coupon.
		$coupon      = WC_Helper_Coupon::create_coupon();
		$coupon_item = new WC_Order_Item_Coupon();
		$coupon_item->set_code( $coupon->get_code() );
		$coupon_item->set_discount( 10 );
		$order->add_item( $coupon_item );

		// Add tax and shipping.
		$order->set_shipping_total( 10 );

		// Add attribution metadata.
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->update_meta_data( '_wc_order_attribution_utm_content', 'YT-TEST-ID' );
		$order->update_meta_data( '_wc_order_attribution_session_entry', 'https://example.com?utm_content=YT-TEST-ID' );

		$order->calculate_totals();
		$order->save();

		$this->middleware->method( 'get_wcs_mca_id' )->willReturn( self::TEST_WCS_MCA_ID );

		// Build the CSV row.
		$row = $this->builder->build_row( $item );

		// Assert.
		$this->assertEquals( $row['transaction_type'], 'purchase' );
		$this->assertSame( self::TEST_WCS_MCA_ID, $row['gmc_merchant_id'] );
		$this->assertEquals( $row['transaction_id'], $order->get_id() );
		$this->assertEquals( $row['item_id'], $item->get_product_id() );
		$this->assertEquals( $row['item_name'], $product->get_name() );
		$this->assertEquals( $row['transaction_date'], $order->get_date_created()->format( 'c' ) );
		$this->assertEquals( $row['refund_date'], '' );
		$this->assertEquals( $row['quantity'], 2 );
		$this->assertEquals( $row['item_unit_price'], 50 );
		$this->assertEquals( $row['item_unit_discounted_price'], 40 );
		$this->assertEquals( $row['item_price'], 100 );
		$this->assertEquals( $row['item_discounted_price'], 80 );
		$this->assertEquals( $row['coupons'], $coupon->get_code() );
		$this->assertEquals( $row['transaction_tax'], 0 );
		$this->assertEquals( $row['transaction_shipping'], 10 );
		$this->assertEquals( $row['transaction_total'], 90 );
		$this->assertEquals( $row['currency_code'], 'USD' );
		$this->assertEquals( $row['landing_page_url'], 'https://example.com?utm_content=YT-TEST-ID' );
		$this->assertEquals( $row['attribution_id'], 'YT-TEST-ID' );
		$this->assertEquals( $row['country_code'], 'US' );
		$this->assertEquals( $row['subaccount_id'], '' );
		$this->assertEquals( $row['reversal_reason'], '' );
	}

	public function test_builds_correct_row_for_refund_order_item() {
		// Create a test product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->save();

		// Create a test order.
		$order = WC_Helper_Order::create_order();

		// Remove the auto-added default item
		foreach ( $order->get_items() as $existing_item ) {
			$order->remove_item( $existing_item->get_id() );
		}

		// Add test product to the test order.
		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$item->set_total( 50 );
		$item->set_subtotal( 50 );
		$order->add_item( $item );

		// Add attribution metadata.
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->update_meta_data( '_wc_order_attribution_utm_content', 'YT-TEST-ID' );
		$order->update_meta_data( '_wc_order_attribution_session_entry', 'https://example.com?utm_content=YT-TEST-ID' );

		$order->set_shipping_total( 10 );
		$order->calculate_totals();
		$order->save();

		$this->middleware->method( 'get_wcs_mca_id' )->willReturn( self::TEST_WCS_MCA_ID );

		// Add the refund.
		$refund = wc_create_refund(
			[
				'order_id'   => $order->get_id(),
				'amount'     => 50,
				'reason'     => 'Test refund',
				'line_items' => [
					$item->get_id() => [
						'qty'          => 1,
						'refund_total' => -50,
						'refund_tax'   => [ 0 => 0 ],
					],
				],
			]
		);

		$refund_item = array_values( $refund->get_items() )[0];

		// Build the CSV row.
		$row = $this->builder->build_row( $refund_item );

		// Assert.
		$this->assertEquals( $row['transaction_type'], 'refund' );
		$this->assertSame( self::TEST_WCS_MCA_ID, $row['gmc_merchant_id'] );
		$this->assertEquals( $row['transaction_id'], $refund->get_id() );
		$this->assertEquals( $row['item_id'], $item->get_product_id() );
		$this->assertEquals( $row['item_name'], $product->get_name() );
		$this->assertEquals( $row['transaction_date'], $order->get_date_created()->format( 'c' ) );
		$this->assertEquals( $row['refund_date'], $refund->get_date_created()->date( 'c' ) );
		$this->assertEquals( $row['quantity'], 1 );
		$this->assertEquals( $row['item_unit_price'], 50 );
		$this->assertEquals( $row['item_unit_discounted_price'], 50 );
		$this->assertEquals( $row['item_price'], 50 );
		$this->assertEquals( $row['item_discounted_price'], 50 );
		$this->assertEquals( $row['coupons'], '' );
		$this->assertEquals( $row['transaction_tax'], 0 );
		$this->assertEquals( $row['transaction_shipping'], 10 );
		$this->assertEquals( $row['transaction_total'], 60 );
		$this->assertEquals( $row['currency_code'], 'USD' );
		$this->assertEquals( $row['landing_page_url'], 'https://example.com?utm_content=YT-TEST-ID' );
		$this->assertEquals( $row['attribution_id'], 'YT-TEST-ID' );
		$this->assertEquals( $row['country_code'], 'US' );
		$this->assertEquals( $row['subaccount_id'], '' );
		$this->assertEquals( $row['reversal_reason'], 'Test refund' );
	}

	public function test_exception_thrown_if_mca_id_cannot_be_retrieved() {
		$this->middleware->method( 'get_wcs_mca_id' )
			->willThrowException( new Exception( 'Invalid response when retrieving MCA ID from WooCommerce Connect Server.' ) );

		// Create a test product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 40 );
		$product->save();

		// Create a test order.
		$order = WC_Helper_Order::create_order();

		// Remove the auto-added default item
		foreach ( $order->get_items() as $existing_item ) {
			$order->remove_item( $existing_item->get_id() );
		}

		// Add test product to the test order.
		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 2 );
		$item->set_total( 80 );
		$item->set_subtotal( 100 );
		$order->add_item( $item );

		// Add a coupon.
		$coupon      = WC_Helper_Coupon::create_coupon();
		$coupon_item = new WC_Order_Item_Coupon();
		$coupon_item->set_code( $coupon->get_code() );
		$coupon_item->set_discount( 10 );
		$order->add_item( $coupon_item );

		// Add tax and shipping.
		$order->set_shipping_total( 10 );

		// Add attribution metadata.
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->update_meta_data( '_wc_order_attribution_utm_content', 'YT-TEST-ID' );
		$order->update_meta_data( '_wc_order_attribution_session_entry', 'https://example.com?utm_content=YT-TEST-ID' );

		$order->calculate_totals();
		$order->save();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid response when retrieving MCA ID from WooCommerce Connect Server.' );

		// Build the CSV row.
		$this->builder->build_row( $item );
	}
}
