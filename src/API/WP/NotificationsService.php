<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WP;

use Automattic\Jetpack\Connection\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Jetpack_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationsService
 * This class implements a service to Notify a partner about Shop Data Updates
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WP
 */
class NotificationsService implements Service, OptionsAwareInterface {

	use OptionsAwareTrait;
	use PluginHelper;

	// List of Topics to be used.
	public const TOPIC_PRODUCT_CREATED  = 'product.create';
	public const TOPIC_PRODUCT_DELETED  = 'product.delete';
	public const TOPIC_PRODUCT_UPDATED  = 'product.update';
	public const TOPIC_COUPON_CREATED   = 'coupon.create';
	public const TOPIC_COUPON_DELETED   = 'coupon.delete';
	public const TOPIC_COUPON_UPDATED   = 'coupon.update';
	public const TOPIC_SHIPPING_UPDATED = 'shipping.update';
	public const TOPIC_SETTINGS_UPDATED = 'settings.update';

	public const DATATYPE_PRODUCT  = 'products';
	public const DATATYPE_COUPON   = 'coupons';
	public const DATATYPE_SHIPPING = 'shipping';
	public const DATATYPE_SETTINGS = 'settings';

	// Constant used to get all the allowed topics
	public const ALLOWED_TOPICS = [
		self::TOPIC_PRODUCT_CREATED,
		self::TOPIC_PRODUCT_DELETED,
		self::TOPIC_PRODUCT_UPDATED,
		self::TOPIC_COUPON_CREATED,
		self::TOPIC_COUPON_DELETED,
		self::TOPIC_COUPON_UPDATED,
		self::TOPIC_SHIPPING_UPDATED,
		self::TOPIC_SETTINGS_UPDATED,
	];

	public const PRODUCT_TOPICS = [
		self::TOPIC_PRODUCT_CREATED,
		self::TOPIC_PRODUCT_UPDATED,
		self::TOPIC_PRODUCT_DELETED,
	];

	public const COUPON_TOPICS = [
		self::TOPIC_COUPON_CREATED,
		self::TOPIC_COUPON_UPDATED,
		self::TOPIC_COUPON_DELETED,
	];

	public const SHIPPING_TOPICS = [ self::TOPIC_SHIPPING_UPDATED ];
	public const SETTINGS_TOPICS = [ self::TOPIC_SETTINGS_UPDATED ];

	/**
	 * The url to send the notification
	 *
	 * @var string $notification_url
	 */
	private $notification_url;

	/**
	 * The WordPress.com blog ID
	 *
	 * @var int $blog_id
	 */
	private $blog_id;

	/**
	 * The Merchant center service
	 *
	 * @var MerchantCenterService $merchant_center
	 */
	public MerchantCenterService $merchant_center;

	/**
	 * The AccountService service
	 *
	 * @var AccountService $account_service
	 */
	public AccountService $account_service;

	/**
	 * NotificationsService constructor
	 *
	 * @param MerchantCenterService $merchant_center
	 * @param AccountService        $account_service
	 */
	public function __construct( MerchantCenterService $merchant_center, AccountService $account_service ) {
		$this->blog_id          = Jetpack_Options::get_option( 'id' );
		$this->merchant_center  = $merchant_center;
		$this->account_service  = $account_service;
		$this->notification_url = "https://public-api.wordpress.com/wpcom/v2/sites/{$this->blog_id}/partners/google/notifications";
	}

	/**
	 * Calls the Notification endpoint in WPCOM.
	 * https://public-api.wordpress.com/wpcom/v2/sites/{site}/partners/google/notifications
	 *
	 * @param string   $topic The topic to use in the notification.
	 * @param int|null $item_id The item ID to notify. It can be null for topics that doesn't need Item ID
	 * @param array    $data Optional data to send in the request.
	 * @return bool True is the notification is successful. False otherwise.
	 */
	public function notify( string $topic, $item_id = null, $data = [] ): bool {
		$is_valid_topic        = in_array( $topic, self::ALLOWED_TOPICS, true );
		$is_ready_for_datatype = $this->is_ready( $this->get_datatype_from_topic( $topic ) );

		/**
		 * Allow users to disable the notification request.
		 *
		 * @since 2.8.0
		 *
		 * @param bool $value The current filter value. True by default.
		 * @param int $item_id The item_id for the notification.
		 * @param string $topic The topic for the notification.
		 */
		if ( ! apply_filters( 'woocommerce_gla_notify', $is_ready_for_datatype && $is_valid_topic, $item_id, $topic ) ) {
			$error_data = [
				'message'                     => 'Notification was not sent because the Notification Service is not ready or the topic is not valid.',
				'data_type'                   => $this->get_datatype_from_topic( $topic ),
				'topic_is_valid'              => $this->yes_or_no( $is_valid_topic ),
				'wpcom_healthy'               => $this->yes_or_no( $this->account_service->is_wpcom_api_status_healthy() ),
				'mc_sync_ready'               => $this->yes_or_no( $this->merchant_center->is_ready_for_syncing() ),
				'notification_service_status' => $this->enabled_or_disabled( $this->is_enabled() ),
				'datatype_sync_status'        => $this->enabled_or_disabled( $is_ready_for_datatype ),
			];

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			$this->notification_error( $topic, print_r( $error_data, true ), $item_id );
			return false;
		}

		$remote_args = [
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => [
				'x-woocommerce-topic' => $topic,
				'Content-Type'        => 'application/json',
			],
			'body'    => array_merge(
				$data,
				[
					'item_id' => $item_id,
					'blog_id' => $this->blog_id,
				]
			),
			'url'     => $this->get_notification_url(),
		];

		$response = $this->do_request( $remote_args );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) {
			$error = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
			$this->notification_error( $topic, $error, $item_id );
			return false;
		}

		do_action(
			'woocommerce_gla_debug_message',
			sprintf( 'Notification - Item ID: %s - Topic: %s - Data %s', $item_id, $topic, wp_json_encode( $data ) ),
			__METHOD__
		);

		return true;
	}

	/**
	 * Logs an error.
	 *
	 * @param string   $topic
	 * @param string   $error
	 * @param int|null $item_id
	 */
	private function notification_error( string $topic, string $error, $item_id = null ): void {
		do_action(
			'woocommerce_gla_error',
			sprintf( 'Error sending notification for Item ID %s with topic %s. %s', $item_id, $topic, $error ),
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
	 * If the Notifications are ready
	 * This happens when the feature is enabled and Merchant Center is ready for syncing.
	 *
	 * @param string|null $data_type The data type to check.
	 * @param bool        $with_health_check If true. Performs a remote request to WPCOM API to get the status.
	 *        * @return bool
	 */
	public function is_ready( string $data_type = null, bool $with_health_check = true ): bool {
		$is_ready = $this->is_enabled() && $this->merchant_center->is_ready_for_syncing() && ( $with_health_check === false || $this->account_service->is_wpcom_api_status_healthy() );
		return $is_ready && ( is_null( $data_type ) || $this->is_pull_enabled_for_datatype( $data_type ) );
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
	 * Get the DataType from a Notification Topic
	 *
	 * @param string $topic The topic.
	 * @return string The DataType
	 */
	protected function get_datatype_from_topic( string $topic ): string {
		if ( in_array( $topic, self::PRODUCT_TOPICS, true ) ) {
			return self::DATATYPE_PRODUCT;
		}

		if ( in_array( $topic, self::COUPON_TOPICS, true ) ) {
			return self::DATATYPE_COUPON;
		}

		if ( in_array( $topic, self::SHIPPING_TOPICS, true ) ) {
			return self::DATATYPE_SHIPPING;
		}

		if ( in_array( $topic, self::SETTINGS_TOPICS, true ) ) {
			return self::DATATYPE_SETTINGS;
		}

		return $topic;
	}

	/**
	 * Get the current value for the API PULL / MC PUSH Sync mode.
	 * Notice that malformed data will be replaced by default data.
	 *
	 * @return array
	 */
	public function get_current_sync_mode(): array {
		$sync_mode = $this->options->get( OptionsInterface::API_PULL_SYNC_MODE );

		if ( ! is_array( $sync_mode ) ) {
			$sync_mode = $this->get_default_sync_mode();
		}

		$sync_mode = array_replace_recursive( $this->get_default_sync_mode(), $sync_mode );
		return apply_filters( 'woocommerce_gla_sync_mode', $sync_mode );
	}

	/**
	 * Check if API PULL is enabled for a specific data type.
	 * Checking a non-existent data type will return false.
	 *
	 * @param string $data_type The data type to check.
	 * @return bool
	 */
	public function is_pull_enabled_for_datatype( string $data_type ): bool {
		$sync_modes = $this->get_current_sync_mode();
		return (bool) apply_filters( 'woocommerce_gla_is_pull_enabled_for_datatype', $sync_modes[ $data_type ]['pull'] ?? false, $data_type );
	}

	/**
	 * Check if MC PUSH is enabled for a specific data type.
	 * Checking a non-existent data type will return false.
	 *
	 * @param string $data_type The data type to check.
	 * @return bool
	 */
	public function is_push_enabled_for_datatype( string $data_type ): bool {
		$sync_modes = $this->get_current_sync_mode();
		return (bool) apply_filters( 'woocommerce_gla_is_push_enabled_for_datatype', $sync_modes[ $data_type ]['push'] ?? false, $data_type );
	}

	/**
	 * Get the default config for the sync mode.
	 *
	 * @return array[]
	 */
	public function get_default_sync_mode(): array {
		$default_mode = [
			'pull' => true,
			'push' => true,
		];

		return [
			self::DATATYPE_PRODUCT  => $default_mode,
			self::DATATYPE_COUPON   => $default_mode,
			self::DATATYPE_SHIPPING => $default_mode,
			self::DATATYPE_SETTINGS => $default_mode,
		];
	}
}
