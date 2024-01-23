<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\Jetpack\Connection\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;
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
	public const TOPIC_PRODUCT_CREATED  = 'product.create';
	public const TOPIC_PRODUCT_DELETED  = 'product.delete';
	public const TOPIC_PRODUCT_UPDATED  = 'product.update';
	public const TOPIC_COUPON_CREATED   = 'coupon.create';
	public const TOPIC_COUPON_DELETED   = 'coupon.delete';
	public const TOPIC_COUPON_UPDATED   = 'coupon.update';
	public const TOPIC_SHIPPING_SAVED   = 'action.woocommerce_after_shipping_zone_object_save';
	public const TOPIC_SHIPPING_DELETED = 'action.woocommerce_delete_shipping_zone';

	/**
	 * The route to send the notification
	 *
	 * @var string $route
	 */
	private $route;

	/**
	 * The product repository dependency
	 *
	 * @var ProductRepository $product_repository
	 */
	protected $product_repository;

	/**
	 * The product helper dependency
	 *
	 * @var ProductHelper $product_helper
	 */
	protected $product_helper;

	/**
	 * Class constructor
	 *
	 * @param ProductRepository $product_repository
	 * @param ProductHelper     $product_helper
	 */
	public function __construct(
		ProductRepository $product_repository,
		ProductHelper $product_helper,
	) {
		$this->product_repository = $product_repository;
		$this->product_helper     = $product_helper;
		$blog_id                  = Jetpack_Options::get_option( 'id' );
		$this->route              = "https://public-api.wordpress.com/wpcom/v2/sites/{$blog_id}/partners/google/notifications";
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
			'url'     => $this->get_route(),
		];

		$response = $this->do_request( $remote_args );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) {
			$error = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
			$this->notification_error( $item_id, $topic, $error );
			return false;
		}

		$this->set_status( $item_id, $this->get_after_notification_status( $topic ) );

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
	 * If the Notifications are enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return apply_filters( 'woocommerce_gla_notifications_enabled', true );
	}

	/**
	 * Find a product matching the provided status
	 *
	 * @param int $product_id The product Id to filter
	 * @param string $topic The product topic to filter
	 *
	 * @return int|null
	 */
	public function filter_product( $product_id, $topic ) {
		if ( is_null( $product_id ) || is_null( $topic ) ) {
			return null;
		}

		$status = $this->get_before_notification_status( $topic );
		$query_results = $this->product_repository->find_notification_products( $product_id, $status );
		return $query_results[ 0 ] ?? null;
	}

	/**
	 * Set the notification status for a product.
	 *
	 * @param int    $product_id
	 * @param string $status
	 */
	public function set_status( $product_id, $status ) {
		$product = $this->product_helper->get_wc_product( $product_id );
		$this->product_helper->set_notification_status( $product, $status );
	}

	/**
	 * Get the Notification Status after the notification happens
	 *
	 * @param string $topic
	 * @return string
	 */
	public function get_after_notification_status( $topic ) {
		if ( str_contains( $topic, '.create' ) ) {
			return NotificationStatus::NOTIFICATION_CREATED;
		} elseif ( str_contains( $topic, '.delete' ) ) {
			return NotificationStatus::NOTIFICATION_DELETED;
		} else {
			return NotificationStatus::NOTIFICATION_UPDATED;
		}
	}

	/**
	 * Get the Notification Status before the notification happens
	 *
	 * @param string $topic
	 * @return string
	 */
	public function get_before_notification_status( $topic ) {
		if ( str_contains( $topic, '.create' ) ) {
			return NotificationStatus::NOTIFICATION_PENDING_CREATE;
		} elseif ( str_contains( $topic, '.delete' ) ) {
			return NotificationStatus::NOTIFICATION_PENDING_DELETE;
		} else {
			return NotificationStatus::NOTIFICATION_PENDING_UPDATE;
		}
	}
}
