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

	public const TOPIC_PRODUCT_CREATED = 'product.create';
	public const TOPIC_PRODUCT_DELETED = 'product.delete';
	public const TOPIC_PRODUCT_UPDATED = 'product.update';
	public const TOPIC_COUPON_CREATED  = 'coupon.create';
	public const TOPIC_COUPON_DELETED  = 'coupon.delete';
	public const TOPIC_COUPON_UPDATED  = 'coupon.update';

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
		$date = new \DateTime();

		do_action(
			'woocommerce_gla_debug_message',
			sprintf( 'Notify: %d  Topic: %s Date: %s', $item_id, $topic, $date->format( 'Y-m-d H:i' ) ),
			__METHOD__,
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
			'url'     => $this->get_route(),
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

	/**
	 * Whether Notifications are enabled
	 *
	 * @return bool True when it is enabled. False otherwise.
	 */
	public function is_enabled(): bool {
		return (bool) apply_filters( 'woocommerce_gla_notifications_enabled', true );
	}

	/**
	 * Whether a topic is product based.
	 *
	 * @param string $topic
	 *
	 * @return bool true if the topic is product based.
	 */
	public function is_product_topic( string $topic ): bool {
		return in_array( $topic, [ self::TOPIC_PRODUCT_CREATED, self::TOPIC_PRODUCT_UPDATED, self::TOPIC_PRODUCT_DELETED ], true );
	}
}
