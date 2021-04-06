<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsSyncController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class SettingsSyncController extends BaseController {

	use EmptySchemaPropertiesTrait;
	use WPErrorTrait;

	/** @var Settings */
	protected $settings;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param Settings   $settings
	 */
	public function __construct( RESTServer $server, Settings $settings ) {
		parent::__construct( $server );
		$this->settings = $settings;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$this->register_route(
			'mc/settings/sync',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_sync_endpoint_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback for syncing shipping.
	 *
	 * @return callable
	 */
	protected function get_sync_endpoint_callback(): callable {
		return function( Request $request ) {
			try {
				$this->settings->sync_taxes();
				$this->settings->sync_shipping();

				do_action( 'gla_mc_settings_sync' );

				return new Response(
					[
						'status'  => 'success',
						'message' => __( 'Successfully synchronized settings with Google.', 'google-listings-and-ads' ),
					],
					201
				);
			} catch ( Exception $e ) {
				do_action( 'gla_exception', $e, __METHOD__ );

				try {
					$decoded = $this->json_decode_message( $e->getMessage() );
					$data    = [
						'status'  => $decoded['code'] ?? 500,
						'message' => $decoded['message'] ?? '',
						'data'    => $decoded,
					];
				} catch ( Exception $e2 ) {
					$data = [
						'status' => 500,
					];
				}

				return $this->error_from_exception(
					$e,
					'gla_setting_sync_error',
					$data
				);
			}
		};
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'settings_sync';
	}
}
