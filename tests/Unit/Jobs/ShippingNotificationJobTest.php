<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\ShippingNotificationJob;

/**
 * Class ShippingNotificationJobTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ShippingNotificationJobTest extends AbstractNotificationJobTest {


	public function get_topic() {
		return 'shipping.update';
	}

	public function get_job_name() {
		return 'shipping';
	}

	public function get_job() {
		return new ShippingNotificationJob(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service,
			$this->merchant_center
		);
	}
}
