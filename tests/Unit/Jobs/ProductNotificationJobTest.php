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
			$this->merchant_center,
			$this->createMock( ProductHelper::class )
		);
	}


	public function create_item() {
		return WC_Helper_Product::create_simple_product();
	}
}
