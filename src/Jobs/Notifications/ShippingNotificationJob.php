<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingNotificationJob
 * Class for the Shipping Notifications
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ShippingNotificationJob extends AbstractNotificationJob {

	/**
	 * Get the job name
	 *
	 * @return string
	 */
	public function get_job_name(): string {
		return 'shipping';
	}


	/**
	 * Logic when processing the items
	 *
	 * @param array $args Arguments for the notification
	 */
	protected function process_items( array $args ): void {
		$this->notifications_service->notify( $this->notifications_service::TOPIC_SHIPPING_UPDATED, null, $args );
	}
}
