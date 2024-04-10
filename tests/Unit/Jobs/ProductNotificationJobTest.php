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

	public function test_sends_offer_id_on_delete() {
		$item  = $this->create_item();
		$id    = $item->get_id();
		$topic = $this->get_topic_name() . '.delete';

		$this->job->get_helper()->expects( $this->once() )
					->method( 'should_trigger_delete_notification' )
					->willReturn( true );

		$this->notification_service->expects( $this->once() )->method( 'notify' )->with(
			$topic,
			$id,
			[ 'offer_id' => "gla_{$id}" ]
		)->willReturn( true );

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $topic,
			]
		);
	}

	public function test_sends_filtered_offer_id_on_delete() {
		$item  = $this->create_item();
		$id    = $item->get_id();
		$topic = $this->get_topic_name() . '.delete';

		add_filter(
			'woocommerce_gla_get_google_product_offer_id',
			function ( $value, $product_id ) {
				return "custom_{$product_id}";
			},
			10,
			2
		);

		$this->job->get_helper()->expects( $this->once() )
					->method( 'should_trigger_delete_notification' )
					->willReturn( true );

		$this->notification_service->expects( $this->once() )->method( 'notify' )->with(
			$topic,
			$id,
			[ 'offer_id' => "custom_{$id}" ]
		)->willReturn( true );

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $topic,
			]
		);

		remove_filter(
			'woocommerce_gla_get_google_product_offer_id',
			function ( $value, $product_id ) {
				return "custom_{$product_id}";
			}
		);
	}
}
