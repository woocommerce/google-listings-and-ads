<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Interface HelperNotificationInterface
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
interface HelperNotificationInterface {

	/**
	 * Checks if the item can be processed based on the topic.
	 *
	 * @param WC_Product|WC_Coupon $item
	 *
	 * @return bool
	 */
	public function should_trigger_create_notification( $item ): bool;


	/**
	 * Indicates if the item ready for sending a delete Notification.
	 *
	 * @param WC_Product|WC_Coupon $item
	 *
	 * @return bool
	 */
	public function should_trigger_delete_notification( $item ): bool;

	/**
	 * Indicates if the item ready for sending an update Notification.
	 *
	 * @param WC_Product|WC_Coupon $item
	 *
	 * @return bool
	 */
	public function should_trigger_update_notification( $item ): bool;

	/**
	 * Marks the item as unsynced.
	 *
	 * @param WC_Product|WC_Coupon $item
	 *
	 * @return void
	 */
	public function mark_as_unsynced( $item ): void;

	/**
	 * Set the notification status for an item.
	 *
	 * @param WC_Product|WC_Coupon $item
	 * @param string               $status
	 *
	 * @return void
	 */
	public function set_notification_status( $item, $status ): void;

	/**
	 * Marks the item as notified.
	 *
	 * @param WC_Product|WC_Coupon $item
	 *
	 * @return void
	 */
	public function mark_as_notified( $item ): void;
}
