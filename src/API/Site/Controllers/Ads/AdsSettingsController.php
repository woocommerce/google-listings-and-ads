<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsSettingsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AdsSettingsController extends BaseOptionsController {

	/**
	 * AdsSettingsController constructor.
	 *
	 * @param RESTServer   $server
	 */
	public function __construct( RESTServer $server ) {
		parent::__construct( $server );
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/settings',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_settings_endpoint_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_settings_endpoint_edit_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_endpoint_params()
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get an array of allowed options.
	 *
	 * @return array
	 */
	protected function get_allowed_options(): array {
		return [
			OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED => true,
		];
	}

	/**
	 * Get a callback for the settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_read_callback(): callable {
		return function () {
			if ( 0 === $this->options->get_ads_id() ) {
				return new WP_REST_Response(
					__( 'Not Allowed.', 'google-listings-and-ads' ),
					403
				);
			}

			$settings = [];

			foreach ( $this->get_allowed_options() as $key => $default ) {
				$settings[ $key ] = $this->options->get( $key, $default );
			}

			return $settings;
		};
	}

	/**
	 * Get a callback for editing the settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_edit_callback(): callable {
		return function ( Request $request ) {
			$params          = $request->get_params();
			$allowed_options = $this->get_allowed_options();
			$settings        = [];

			foreach ( $params as $key => $value ) {
				if ( ! array_key_exists( $key, $allowed_options ) ) {
					continue;
				}

				$result = $this->options->update( $key, $value );

				if ( false === $result ) {
					return new WP_REST_Response(
						__( 'Unable to update setting.', 'google-listings-and-ads' ),
						403
					);
				}

				$settings[ $key ] = $value;
			}

			return $settings;
		};
	}

	/**
	 * Get requests parameters for the settings endpoint.
	 *
	 * @return array
	 */
	protected function get_endpoint_params(): array {
		return [
			'enhanced_conversions_enabled' => [
				'type'        => 'boolean',
				'description' => __(
					'Whether enhanced conversions are enabled.',
					'google-listings-and-ads'
				),
			],
		];
	}

	/**
	 * Get the schema for settings endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'enhanced_conversions_enabled' => [
				'type'        => 'boolean',
				'description' => __(
					'Whether enhanced conversions are enabled.',
					'google-listings-and-ads'
				),
				'context'     => [ 'view', 'edit' ],
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
		return 'ads_settings';
	}
}
