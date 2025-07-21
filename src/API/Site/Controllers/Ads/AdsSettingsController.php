<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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
					'args'                => $this->get_endpoint_params(),
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
			OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED => false,
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

			return new WP_REST_Response( $settings );
		};
	}

	/**
	 * Get a callback for editing the ads settings endpoint.
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

				$stored = $this->options->get( $key );

				if ( is_null( $stored ) ) {
					$this->options->add( $key, $value );
					$settings[ $key ] = $value;
					continue;
				} elseif ( (bool) $stored === $value ) {
					$settings[ $key ] = $value;
					continue;
				}

				$result = $this->options->update( $key, $value );

				if ( false === $result ) {
					return new WP_REST_Response(
						__( 'Unable to update setting.', 'google-listings-and-ads' ),
						400
					);
				}

				$settings[ $key ] = $value;
			}

			return new WP_REST_Response( $settings );
		};
	}

	/**
	 * Get requests parameters for the ads settings endpoint.
	 *
	 * @return array
	 */
	protected function get_endpoint_params(): array {
		return [
			OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED => [
				'type'        => 'boolean',
				'description' => __(
					'Whether enhanced conversions are enabled.',
					'google-listings-and-ads'
				),
			],
		];
	}

	/**
	 * Get the schema for ads settings endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			OptionsInterface::ADS_ENHANCED_CONVERSIONS_ENABLED => [
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
