<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectController
 *
 * Handles the /wc/gla/connect endpoint for Google-requested account linking.
 * When Google calls this endpoint, it triggers a call to the external /account:connect endpoint.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class ConnectController extends BaseController {

	/**
	 * Service used to make external account connect calls.
	 *
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * Options service to access merchant ID.
	 *
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * ConnectController constructor.
	 *
	 * @param RESTServer       $server
	 * @param Middleware       $middleware
	 * @param OptionsInterface $options
	 */
	public function __construct( RESTServer $server, Middleware $middleware, OptionsInterface $options ) {
		parent::__construct( $server );
		$this->middleware = $middleware;
		$this->options    = $options;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'connect',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the connect request.
	 *
	 * @return callable
	 */
	protected function get_connect_callback(): callable {
		return function ( Request $request ) {
			try {
				// Trigger the account connection
				$this->middleware->update_sdi_merchant_account();

				$result = [
					'status'             => 'success',
					'message'            => __( 'Account connection triggered successfully.', 'google-listings-and-ads' ),
					'merchant_center_id' => $this->options->get_merchant_id(),
					'blog_id'            => \Jetpack_Options::get_option( 'id' ),
				];

				return $this->prepare_item_for_response( $result, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the schema for the connect endpoint.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'status'             => [
				'description' => __( 'Status of the connection request.', 'google-listings-and-ads' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'message'            => [
				'description' => __( 'Message describing the connection result.', 'google-listings-and-ads' ),
				'type'        => 'string',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'merchant_center_id' => [
				'description' => __( 'The Merchant Center ID that was connected.', 'google-listings-and-ads' ),
				'type'        => 'integer',
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'blog_id'            => [
				'description' => __( 'The WordPress.com blog ID.', 'google-listings-and-ads' ),
				'type'        => 'integer',
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
		return 'connect';
	}
}
