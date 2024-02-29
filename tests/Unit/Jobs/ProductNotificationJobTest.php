<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\ProductNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use WC_Helper_Product;

/**
 * Class ProductNotificationJobTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ProductNotificationJobTest extends AbstractItemNotificationJobTest {

	public function get_job_name() {
		return 'products';
	}

	public function get_topic_name() {
		return 'product';
	}

	public function get_job() {
		return new ProductNotificationJob(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service,
			$this->createMock( ProductHelper::class )
		);
	}


	public function create_item() {
		return WC_Helper_Product::create_simple_product();
	}

	public function test_mark_as_unsynced_when_delete() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_delete_notification' )
			->with( $product )
			->willReturn( true );

		$this->product_helper->expects( $this->exactly( 3 ) )
			->method( 'get_wc_product' )
			->with( $id )
			->willReturn( $product );

		$this->notification_service->expects( $this->once() )->method( 'notify' )->willReturn( true );
		$this->product_helper->expects( $this->once() )
			->method( 'mark_as_unsynced' );

		$this->job->handle_process_items_action( [ $id, 'product.delete' ] );
	}
}
