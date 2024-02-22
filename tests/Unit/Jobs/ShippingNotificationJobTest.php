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
		return $this->notification_service::TOPIC_SHIPPING_UPDATED;
	}

	public function get_job_name() {
		return 'shipping';
	}

	public function get_job() {
		return new ShippingNotificationJob(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service
		);
	}
}
