<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsNotificationJob
 * Class for the Settings Notifications
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class SettingsNotificationJob extends AbstractNotificationJob {

	/**
	 * Logic when processing the items
	 *
	 * @param array $args Arguments for the notification
	 */
	protected function process_items( array $args ): void {
		$this->notifications_service->notify( $this->notifications_service::TOPIC_SETTINGS_UPDATED );
	}


	/**
	 * Get the job name
	 *
	 * @return string
	 */
	public function get_job_name(): string {
		return 'settings';
	}
}
