<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

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
		if ( ! isset( $args['item_id'] ) || ! isset( $args['topic'] ) ) {
			do_action(
				'woocommerce_gla_error',
				'Error sending the Notification. Topic and Item ID are mandatory',
				__METHOD__
			);
			return;
		}

		$item  = $args['item_id'];
		$topic = $args['topic'];
		$data  = $args['data'] ?? [];

		if ( $this->can_process( $item, $topic ) && $this->notifications_service->notify( $topic, $item, $data ) ) {
			$this->set_status( $item, $this->get_after_notification_status( $topic ) );
			$this->handle_notified( $topic, $item );
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
		if ( $this->is_create_topic( $topic ) ) {
			return NotificationStatus::NOTIFICATION_CREATED;
		} elseif ( $this->is_delete_topic( $topic ) ) {
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

		if ( $this->is_create_topic( $topic ) ) {
			return $this->get_helper()->should_trigger_create_notification( $item );
		} elseif ( $this->is_delete_topic( $topic ) ) {
			return $this->get_helper()->should_trigger_delete_notification( $item );
		} else {
			return $this->get_helper()->should_trigger_update_notification( $item );
		}
	}

	/**
	 * Handle the item after the notification.
	 *
	 * @param string $topic
	 * @param int    $item
	 */
	protected function handle_notified( string $topic, int $item ): void {
		if ( $this->is_delete_topic( $topic ) ) {
			$this->get_helper()->mark_as_unsynced( $this->get_item( $item ) );
		}

		if ( $this->is_create_topic( $topic ) ) {
			$this->get_helper()->mark_as_notified( $this->get_item( $item ) );
		}
	}

	/**
	 * If a topic is a delete topic
	 *
	 * @param string $topic The topic to check
	 *
	 * @return bool
	 */
	protected function is_delete_topic( $topic ) {
		return str_contains( $topic, '.delete' );
	}

	/**
	 * If a topic is a create topic
	 *
	 * @param string $topic The topic to check
	 *
	 * @return bool
	 */
	protected function is_create_topic( $topic ) {
		return str_contains( $topic, '.create' );
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
	 * @return HelperNotificationInterface
	 */
	abstract public function get_helper(): HelperNotificationInterface;
}
