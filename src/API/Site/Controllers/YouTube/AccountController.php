<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\YouTube;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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
class AccountController extends BaseOptionsController implements ContainerAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
	use PluginHelper;

	/** @var Connection */
	protected $connection;

	/** @var Client */
	protected $client;

	/**
	 * AccountController constructor.
	 *
	 * @param RESTServer $server
	 * @param Connection $connection
	 * @param Client     $client
	 */
	public function __construct( RESTServer $server, Connection $connection, Client $client ) {
		parent::__construct( $server );

		$this->connection = $connection;
		$this->client     = $client;
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
				// Get store information.
				$store_name = get_bloginfo( 'name' );
				$store_url  = $this->strip_url_protocol( $this->get_site_url() );

				// Get Merchant Center ID.
				$merchant_center = $this->options->get( OptionsInterface::MERCHANT_CENTER );
				if ( empty( $merchant_center['id'] ) ) {
					throw new Exception(
						__( 'Merchant Center account is not configured.', 'google-listings-and-ads' ),
						400
					);
				}

				$merchant_id = (string) $merchant_center['id'];

				// Build request body.
				$body = [
					'snippet' => [
						'type'               => 'channelToStoreLink',
						'channelToStoreLink' => [
							'storeName'  => $store_name,
							'storeUrl'   => $store_url,
							'merchantId' => $merchant_id,
						],
					],
				];

				// Make request to WCS proxy.
				$result = $this->client->post(
					$this->get_third_party_links_url(),
					[
						'body' => wp_json_encode( $body ),
					]
				);

				$response = json_decode( $result->getBody()->getContents(), true );

				if ( 200 === $result->getStatusCode() ) {
					return [
						'status'  => 'success',
						'message' => __( 'Successfully completed YouTube setup.', 'google-listings-and-ads' ),
					];
				}

				do_action( 'woocommerce_gla_guzzle_invalid_response', $response, __METHOD__ );

				$message = $response['message'] ?? __( 'Unable to complete YouTube setup.', 'google-listings-and-ads' );
				throw new Exception( $message, $result->getStatusCode() );
			} catch ( ClientExceptionInterface $e ) {
				do_action( 'woocommerce_gla_guzzle_client_exception', $e, __METHOD__ );

				return $this->response_from_exception(
					new Exception(
						$this->client_exception_message(
							$e,
							__( 'Unable to complete YouTube setup.', 'google-listings-and-ads' )
						),
						400
					)
				);
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
