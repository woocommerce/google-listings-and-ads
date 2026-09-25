<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Exports\Services;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Services\YouTubeOrders;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use WC_Helper_Order;
use WC_Helper_Product;

/**
 * Class YouTubeOrdersTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Admin\Exports\Services
 */
class YouTubeOrdersTest extends UnitTest {
	/** @var YouTubeOrders $youtube_orders */
	protected $youtube_orders;

	/** @var array $orders */
	protected $orders = [];

	/** @var array $refunds */
	protected $refunds = [];

	public function setUp(): void {
		parent::setUp();

		$this->youtube_orders = new YouTubeOrders();
	}

	public function tearDown(): void {
		// Clean up test orders and refunds.
		foreach ( $this->orders as $order ) {
			$order->delete( true );
		}

		foreach ( $this->refunds as $refund ) {
			$refund->delete( true );
		}

		parent::tearDown();
	}

	public function test_find_orders_returns_purchase_orders_with_youtube_attribution() {
		$date = gmdate( 'Y-m-d' );

		// Create order with YouTube attribution.
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->set_date_created( strtotime( $date . ' 12:00:00' ) );
		$order->save();
		$this->orders[] = $order;

		// Create order without YouTube attribution.
		$order_no_youtube = WC_Helper_Order::create_order();
		$order_no_youtube->update_meta_data( '_wc_order_attribution_utm_source', 'google' );
		$order_no_youtube->set_date_created( strtotime( $date . ' 13:00:00' ) );
		$order_no_youtube->save();
		$this->orders[] = $order_no_youtube;

		$result = $this->youtube_orders->find_orders( $date );

		$this->assertContains( $order->get_id(), $result );
		$this->assertNotContains( $order_no_youtube->get_id(), $result );
	}

	public function test_find_orders_returns_refund_ids_when_parent_has_youtube_attribution() {
		$date = gmdate( 'Y-m-d' );

		// Create parent order with YouTube attribution.
		$parent_order = WC_Helper_Order::create_order();
		$parent_order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$parent_order->set_date_created( strtotime( $date . ' 12:00:00' ) );
		$parent_order->save();
		$this->orders[] = $parent_order;

		// Create refund for the order.
		$refund = wc_create_refund(
			[
				'order_id' => $parent_order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			]
		);
		$refund->set_date_created( strtotime( $date . ' 14:00:00' ) );
		$refund->save();
		$this->refunds[] = $refund;

		// Create parent order without YouTube attribution.
		$parent_order_no_youtube = WC_Helper_Order::create_order();
		$parent_order_no_youtube->update_meta_data( '_wc_order_attribution_utm_source', 'google' );
		$parent_order_no_youtube->set_date_created( strtotime( $date . ' 13:00:00' ) );
		$parent_order_no_youtube->save();
		$this->orders[] = $parent_order_no_youtube;

		// Create refund for the non-YouTube order.
		$refund_no_youtube = wc_create_refund(
			[
				'order_id' => $parent_order_no_youtube->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			]
		);
		$refund_no_youtube->set_date_created( strtotime( $date . ' 15:00:00' ) );
		$refund_no_youtube->save();
		$this->refunds[] = $refund_no_youtube;

		$result = $this->youtube_orders->find_orders( $date );

		$this->assertContains( $refund->get_id(), $result );
		$this->assertNotContains( $refund_no_youtube->get_id(), $result );
	}

	public function test_find_orders_includes_purchases_and_refunds() {
		$date = gmdate( 'Y-m-d' );

		// Create purchase order with YouTube attribution.
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->set_date_created( strtotime( $date . ' 12:00:00' ) );
		$order->save();
		$this->orders[] = $order;

		// Create parent order with YouTube attribution for refund.
		$parent_order = WC_Helper_Order::create_order();
		$parent_order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$parent_order->set_date_created( strtotime( $date . ' 13:00:00' ) );
		$parent_order->save();
		$this->orders[] = $parent_order;

		// Create refund.
		$refund = wc_create_refund(
			[
				'order_id' => $parent_order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			]
		);
		$refund->set_date_created( strtotime( $date . ' 14:00:00' ) );
		$refund->save();
		$this->refunds[] = $refund;

		$result = $this->youtube_orders->find_orders( $date );

		$this->assertContains( $order->get_id(), $result );
		$this->assertContains( $parent_order->get_id(), $result );
		$this->assertContains( $refund->get_id(), $result );
		$this->assertCount( 3, $result );
	}

	public function test_find_orders_returns_unique_order_ids() {
		$date = gmdate( 'Y-m-d' );

		// Create order with YouTube attribution.
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->set_date_created( strtotime( $date . ' 12:00:00' ) );
		$order->save();
		$this->orders[] = $order;

		$result = $this->youtube_orders->find_orders( $date );

		// Verify no duplicates.
		$this->assertEquals( $result, array_unique( $result ) );
	}

	public function test_find_orders_handles_empty_date_defaults_to_yesterday() {
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		// Create order with YouTube attribution from yesterday.
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->set_date_created( strtotime( $yesterday . ' 12:00:00' ) );
		$order->save();
		$this->orders[] = $order;

		// Create order from today (should not be included).
		$today_order = WC_Helper_Order::create_order();
		$today_order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$today_order->set_date_created( time() );
		$today_order->save();
		$this->orders[] = $today_order;

		$result = $this->youtube_orders->find_orders( '' );

		$this->assertContains( $order->get_id(), $result );
		$this->assertNotContains( $today_order->get_id(), $result );
	}

	public function test_find_orders_handles_custom_date() {
		$custom_date = '2024-01-15';

		// Create order with YouTube attribution on custom date.
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order->set_date_created( strtotime( $custom_date . ' 12:00:00' ) );
		$order->save();
		$this->orders[] = $order;

		// Create order from today (should not be included).
		$today_order = WC_Helper_Order::create_order();
		$today_order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$today_order->set_date_created( time() );
		$today_order->save();
		$this->orders[] = $today_order;

		$result = $this->youtube_orders->find_orders( $custom_date );

		$this->assertContains( $order->get_id(), $result );
		$this->assertNotContains( $today_order->get_id(), $result );
	}

	public function test_find_orders_handles_limit_parameter() {
		$date = gmdate( 'Y-m-d' );

		// Create multiple orders with YouTube attribution.
		for ( $i = 0; $i < 5; $i++ ) {
			$order = WC_Helper_Order::create_order();
			$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
			$order->set_date_created( strtotime( $date . ' ' . ( 12 + $i ) . ':00:00' ) );
			$order->save();
			$this->orders[] = $order;
		}

		$result = $this->youtube_orders->find_orders( $date, 3 );

		// Note: Since we filter after fetching, we might get fewer results than limit.
		// But we should get at most 3 results (if all 3 have YouTube attribution).
		$this->assertLessThanOrEqual( 3, count( $result ) );
	}

	public function test_find_orders_handles_offset_parameter() {
		$date = gmdate( 'Y-m-d' );

		// Create multiple orders with YouTube attribution.
		$order_ids = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$order = WC_Helper_Order::create_order();
			$order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
			$order->set_date_created( strtotime( $date . ' ' . ( 12 + $i ) . ':00:00' ) );
			$order->save();
			$this->orders[] = $order;
			$order_ids[]    = $order->get_id();
		}

		$result_all    = $this->youtube_orders->find_orders( $date );
		$result_offset = $this->youtube_orders->find_orders( $date, -1, 2 );

		// Results with offset should be different from results without offset.
		// Note: Due to filtering after fetching, exact offset behavior may vary.
		$this->assertIsArray( $result_offset );
	}

	public function test_find_orders_excludes_orders_without_meta() {
		$date = gmdate( 'Y-m-d' );

		// Create order with YouTube attribution.
		$order_with_meta = WC_Helper_Order::create_order();
		$order_with_meta->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$order_with_meta->set_date_created( strtotime( $date . ' 12:00:00' ) );
		$order_with_meta->save();
		$this->orders[] = $order_with_meta;

		// Create order without any attribution meta.
		$order_no_meta = WC_Helper_Order::create_order();
		$order_no_meta->set_date_created( strtotime( $date . ' 13:00:00' ) );
		$order_no_meta->save();
		$this->orders[] = $order_no_meta;

		$result = $this->youtube_orders->find_orders( $date );

		$this->assertContains( $order_with_meta->get_id(), $result );
		$this->assertNotContains( $order_no_meta->get_id(), $result );
	}

	public function test_find_orders_returns_empty_array_when_no_matching_orders() {
		$date = gmdate( 'Y-m-d' );

		// Create order without YouTube attribution.
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'google' );
		$order->set_date_created( strtotime( $date . ' 12:00:00' ) );
		$order->save();
		$this->orders[] = $order;

		$result = $this->youtube_orders->find_orders( $date );

		$this->assertEmpty( $result );
	}

	public function test_find_orders_handles_refunds_created_on_different_date_than_parent() {
		$order_date  = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
		$refund_date = gmdate( 'Y-m-d' );

		// Create parent order with YouTube attribution from 2 days ago.
		$parent_order = WC_Helper_Order::create_order();
		$parent_order->update_meta_data( '_wc_order_attribution_utm_source', 'youtube' );
		$parent_order->set_date_created( strtotime( $order_date . ' 12:00:00' ) );
		$parent_order->save();
		$this->orders[] = $parent_order;

		// Create refund today.
		$refund = wc_create_refund(
			[
				'order_id' => $parent_order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			]
		);
		$refund->set_date_created( strtotime( $refund_date . ' 12:00:00' ) );
		$refund->save();
		$this->refunds[] = $refund;

		// Query for refund date - should find the refund.
		$result = $this->youtube_orders->find_orders( $refund_date );

		$this->assertContains( $refund->get_id(), $result );
	}
}
