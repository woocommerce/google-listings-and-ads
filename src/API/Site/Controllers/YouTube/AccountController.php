<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\YouTube;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Client;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Client\ClientExceptionInterface;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\YouTube
 */
class AccountController extends BaseController implements ContainerAwareInterface {

	use ContainerAwareTrait;

	/** @var Connection */
	protected $connection;

	/**
	 * AccountController constructor.
	 *
	 * @param RESTServer $server
	 * @param Connection $connection
	 * @param Client     $client
	 */
	public function __construct( RESTServer $server, Connection $connection ) {
		parent::__construct( $server );

		$this->connection = $connection;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'youtube/connect',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
		$this->register_route(
			'youtube/connection',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connected_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_disconnect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
		$this->register_route(
			'youtube/setup/complete',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_setup_complete_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for the connection request.
	 *
	 * @return callable
	 */
	protected function get_connect_callback(): callable {
		return function () {
			try {
				return [
					'url' => $this->connection->connect(
						admin_url(
							'admin.php?page=wc-admin&path=/google/settings'
						)
					),
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for the disconnection request.
	 *
	 * @return callable
	 */
	protected function get_disconnect_callback(): callable {
		return function () {
			$this->connection->disconnect();

			return [
				'status'  => 'success',
				'message' => __( 'Successfully disconnected.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Get the callback function to determine if YouTube is currently connected.
	 *
	 * @return callable
	 */
	protected function get_connected_callback(): callable {
		return function () {
			try {
				$status     = $this->connection->get_status();
				$connection = isset( $status['status'] ) ? $status['status'] : 'disconnected';
				$channel    = [];

				// Get channel information if connected.
				if ( 'connected' === $connection ) {
					$channels = $this->connection->get_channels();

					if ( isset( $channels['items'] ) && ! empty( $channels['items'] ) ) {
						$details = array_shift( $channels['items'] );

						$channel = [
							'id'    => $details['id'],
							'label' => $details['snippet']['title'],
						];
					}
				}

				return [
					'status'  => $connection,
					'channel' => $channel,
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for completing YouTube setup.
	 *
	 * @return callable
	 */
	protected function get_setup_complete_callback(): callable {
		return function () {
			try {
				return $result = $this->connection->third_party_link();

				if ( isset( $result['status']['linkedStatus'] ) && 'linked' === $result['status']['linkedStatus'] ) {
					return [
						'status'  => 'success',
						'message' => __( 'Successfully completed YouTube setup.', 'google-listings-and-ads' ),
					];
				}

				do_action( 'woocommerce_gla_guzzle_invalid_response', $result, __METHOD__ );

				throw new Exception( __( '2 Unable to complete YouTube setup.', 'google-listings-and-ads' ), 400 );
			} catch ( ClientExceptionInterface $e ) {
				do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

				return $this->response_from_exception( $e );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the YouTube Third Party Links URL.
	 *
	 * @return string
	 */
	protected function get_third_party_links_url(): string {
		return "{$this->container->get( 'connect_server_root' )}google/youtube/v3/thirdPartyLinks?part=snippet";
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'url' => [
				'type'        => 'string',
				'description' => __( 'The URL for making a connection to YouTube.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'youtube_account';
	}
}
