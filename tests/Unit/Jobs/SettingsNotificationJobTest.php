<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\SettingsNotificationJob;

/**
 * Class SettingsNotificationJobTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class SettingsNotificationJobTest extends AbstractNotificationJobTest {


	public function get_topic() {
		return 'settings.update';
	}

	public function get_job_name() {
		return 'settings';
	}

	public function get_job() {
		return new SettingsNotificationJob(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service
		);
	}
}
