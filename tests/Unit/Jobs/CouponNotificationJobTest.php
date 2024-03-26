<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\CouponNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use WC_Helper_Coupon;

/**
 * Class CouponNotificationJobTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class CouponNotificationJobTest extends AbstractItemNotificationJobTest {

	public function get_job_name() {
		return 'coupons';
	}

	public function get_topic_name() {
		return 'coupon';
	}

	public function get_job() {
		return new CouponNotificationJob(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service,
			$this->createMock( CouponHelper::class )
		);
	}

	public function create_item() {
		return WC_Helper_Coupon::create_coupon();
	}
}
