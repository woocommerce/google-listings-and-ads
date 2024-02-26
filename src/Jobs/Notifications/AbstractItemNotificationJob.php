<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractItemNotificationJob
 * Generic class for the Notification Jobs containing items
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
abstract class AbstractItemNotificationJob extends AbstractNotificationJob {

	/**
	 * Logic when processing the items
	 *
	 * @param array $args Arguments with the item id and the topic
	 */
	protected function process_items( array $args ): void {
		if ( ! isset( $args[0] ) || ! isset( $args[1] ) ) {
			do_action(
				'woocommerce_gla_error',
				'Error sending the Notification. Topic and Item ID are mandatory',
				__METHOD__
			);
			return;
		}

		$item  = $args[0];
		$topic = $args[1];

		if ( $this->can_process( $item, $topic ) && $this->notifications_service->notify( $topic, $item ) ) {
			$this->set_status( $item, $this->get_after_notification_status( $topic ) );
			$this->maybe_mark_as_unsynced( $topic, $item );
		}
	}

	/**
	 * Set the notification status for the item.
	 *
	 * @param int    $item_id
	 * @param string $status
	 */
	protected function set_status( int $item_id, string $status ): void {
		$item = $this->get_item( $item_id );
		$this->get_helper()->set_notification_status( $item, $status );
	}

	/**
	 * Get the Notification Status after the notification happens
	 *
	 * @param string $topic
	 * @return string
	 */
	protected function get_after_notification_status( string $topic ): string {
		if ( str_contains( $topic, '.create' ) ) {
			return NotificationStatus::NOTIFICATION_CREATED;
		} elseif ( str_contains( $topic, '.delete' ) ) {
			return NotificationStatus::NOTIFICATION_DELETED;
		} else {
			return NotificationStatus::NOTIFICATION_UPDATED;
		}
	}

	/**
	 * Checks if the item can be processed based on the topic.
	 * This is needed because the item can change the Notification Status before
	 * the Job process the item.
	 *
	 * @param int    $item_id
	 * @param string $topic
	 * @return bool
	 */
	protected function can_process( int $item_id, string $topic ): bool {
		$item = $this->get_item( $item_id );

		if ( str_contains( $topic, '.create' ) ) {
			return $this->get_helper()->should_trigger_create_notification( $item );
		} elseif ( str_contains( $topic, '.delete' ) ) {
			return $this->get_helper()->should_trigger_delete_notification( $item );
		} else {
			return $this->get_helper()->should_trigger_update_notification( $item );
		}
	}

	/**
	 * If there is a valid Item ID and topic is a deletion topic. Mark the item as unsynced.
	 *
	 * @param string $topic
	 * @param int    $item
	 */
	protected function maybe_mark_as_unsynced( string $topic, int $item ): void {
		if ( ! str_contains( $topic, '.delete' ) ) {
			return;
		}

		$item = $this->get_item( $item );
		$this->get_helper()->mark_as_unsynced( $item );
	}

	/**
	 * Get the item
	 *
	 * @param int $item_id
	 * @return \WC_Product|\WC_Coupon
	 */
	abstract protected function get_item( int $item_id );

	/**
	 * Get the helper
	 *
	 * @return CouponHelper|ProductHelper
	 */
	abstract public function get_helper();
}
