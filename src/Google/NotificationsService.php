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
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google\Notifications
 */
class NotificationsService implements Service {

	// List of Topics to be used.
	public const TOPIC_PRODUCT_CREATED   = 'product.created';
	public const TOPIC_PRODUCT_DELETED   = 'product.deleted';
	public const TOPIC_PRODUCT_UPDATED   = 'product.updated';
	public const  TOPIC_COUPON_CREATED   = 'coupon.created';
	public const  TOPIC_COUPON_DELETED   = 'coupon.deleted';
	public const  TOPIC_COUPON_UPDATED   = 'coupon.updated';
	public const  TOPIC_SHIPPING_SAVED   = 'action.woocommerce_after_shipping_zone_object_save';
	public const  TOPIC_SHIPPING_DELETED = 'action.woocommerce_delete_shipping_zone';

	/**
	 * The route to send the notification
	 *
	 * @var string $route
	 */
	private $route;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$blog_id     = Jetpack_Options::get_option( 'id' );
		$this->route = "https://public-api.wordpress.com/wpcom/v2/sites/{$blog_id}/partners/google/notifications";
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
		$remote_args = [
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => [
				'x-woocommerce-topic' => $topic,
			],
			'body'    => [
				'item_id' => $item_id,
			],
			'url'     => $this->get_route(),
		];

		$response = $this->do_request( $remote_args );

		if ( is_wp_error( $response ) ) {
			$this->notification_error( $item_id, $topic, $response->get_error_message() );
			return false;
		}

		if ( wp_remote_retrieve_response_code( $response ) >= 400 ) {
			$this->notification_error( $item_id, $topic, wp_remote_retrieve_body( $response ) );
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
	protected function do_request( $args ) {
		return Client::remote_request( $args, wp_json_encode( $args['body'] ) );
	}

	/**
	 * Get the route
	 *
	 * @return string The route.
	 */
	public function get_route(): string {
		return $this->route;
	}
}
