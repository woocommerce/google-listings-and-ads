<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\Jetpack\Connection\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationsService
 * This class implements a service to Notify a partner about Shop Data Updates
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class NotificationsService implements Service {

	// List of Topics to be used.
	public const TOPIC_PRODUCT_CREATED  = 'product.create';
	public const TOPIC_PRODUCT_DELETED  = 'product.delete';
	public const TOPIC_PRODUCT_UPDATED  = 'product.update';
	public const TOPIC_COUPON_CREATED   = 'coupon.create';
	public const TOPIC_COUPON_DELETED   = 'coupon.delete';
	public const TOPIC_COUPON_UPDATED   = 'coupon.update';
	public const TOPIC_SHIPPING_SAVED   = 'action.woocommerce_after_shipping_zone_object_save';
	public const TOPIC_SHIPPING_DELETED = 'action.woocommerce_delete_shipping_zone';

	/**
	 * The url to send the notification
	 *
	 * @var string $notification_url
	 */
	private $notification_url;


	/**
	 * Class constructor
	 */
	public function __construct() {
		$blog_id                = Jetpack_Options::get_option( 'id' );
		$this->notification_url = "https://public-api.wordpress.com/wpcom/v2/sites/{$blog_id}/partners/google/notifications";
	}

	/**
	 * Calls the Notification endpoint in WPCOM.
	 * https://public-api.wordpress.com/wpcom/v2/sites/{site}/partners/google/notifications
	 *
	 * @param int    $item_id
	 * @param string $topic
	 * @return bool True is the notification is successful. False otherwise.
	 */
	public function notify( int $item_id, string $topic ) {
		do_action(
			'woocommerce_gla_debug_message',
			sprintf( 'Notification - Item ID: %d - Topic: %s', $item_id, $topic ),
			__METHOD__
		);

		$remote_args = [
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => [
				'x-woocommerce-topic' => $topic,
			],
			'body'    => [
				'item_id' => $item_id,
			],
			'url'     => $this->get_notification_url(),
		];

		$response = $this->do_request( $remote_args );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) {
			$error = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
			$this->notification_error( $item_id, $topic, $error );
			return false;
		}

		return true;
	}

	/**
	 * Logs an error.
	 *
	 * @param int    $item_id
	 * @param string $topic
	 * @param string $error
	 */
	private function notification_error( int $item_id, string $topic, string $error ): void {
		do_action(
			'woocommerce_gla_error',
			sprintf( 'Error sending notification for Item ID %d with topic %s. %s', $item_id, $topic, $error ),
			__METHOD__
		);
	}

	/**
	 * Performs a Remote Request
	 *
	 * @param array $args
	 * @return array|\WP_Error
	 */
	protected function do_request( array $args ) {
		return Client::remote_request( $args, wp_json_encode( $args['body'] ) );
	}

	/**
	 * Get the route
	 *
	 * @return string The route.
	 */
	public function get_notification_url(): string {
		return $this->notification_url;
	}

	/**
	 * If the Notifications are enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return apply_filters( 'woocommerce_gla_notifications_enabled', true );
	}
}
