<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class SettingsController extends BaseOptionsController {

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/settings',
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
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get a callback for the settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_read_callback(): callable {
		return function() {
			$data   = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
			$schema = $this->get_schema_properties();
			$items  = [];
			foreach ( $schema as $key => $property ) {
				$items[ $key ] = $data[ $key ] ?? $property['default'] ?? null;
			}

			return $items;
		};
	}

	/**
	 * Get a callback for editing the settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_edit_callback(): callable {
		return function( Request $request ) {
			$schema  = $this->get_schema_properties();
			$options = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
			foreach ( $schema as $key => $property ) {
				$options[ $key ] = $request->get_param( $key ) ?? $options[ $key ] ?? $property['default'] ?? null;
			}

			$this->options->update( OptionsInterface::MERCHANT_CENTER, $options );

			return [
				'status'  => 'success',
				'message' => __( 'Merchant Center Settings successfully updated.', 'google-listings-and-ads' ),
				'data'    => $options,
			];
		};
	}

	/**
	 * Get the schema for settings endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'shipping_rate'           => [
				'type'              => 'string',
				'description'       => __(
					'Whether shipping rate is a simple flat rate or needs to be configured manually in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'flat',
					'manual',
				],
			],
			'offers_free_shipping'    => [
				'type'              => 'boolean',
				'description'       => __(
					'Whether free shipping over a certain price is allowed .',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'free_shipping_threshold' => [
				'type'              => 'integer',
				'description'       => __( 'Minimum price eligible for free shipping.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'shipping_time'           => [
				'type'              => 'string',
				'description'       => __(
					'Whether shipping time is a simple flat time or needs to be configured manually in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'flat',
					'manual',
				],
			],
			'tax_rate'                => [
				'type'              => 'string',
				'description'       => __(
					'Whether tax rate is destination based or need to be configured manually in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'destination',
					'manual',
				],
			],
			'website_live'            => [
				'type'              => 'boolean',
				'description'       => __( 'Whether the store website is live.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'checkout_process_secure' => [
				'type'              => 'boolean',
				'description'       => __(
					'Whether the checkout process is complete and secure.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'payment_methods_visible' => [
				'type'              => 'boolean',
				'description'       => __(
					'Whether the payment methods are visible on the website.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'refund_tos_visible'      => [
				'type'              => 'boolean',
				'description'       => __(
					'Whether the refund policy and terms of service are visible on the website.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'contact_info_visible'    => [
				'type'              => 'boolean',
				'description'       => __(
					'Whether the phone number, email, and/or address are visible on the website.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
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
		return 'merchant_center_settings';
	}
}
