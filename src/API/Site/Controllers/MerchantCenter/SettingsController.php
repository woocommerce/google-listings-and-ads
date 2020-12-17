<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class SettingsController extends BaseController {

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer       $server
	 * @param OptionsInterface $options
	 */
	public function __construct( RESTServer $server, OptionsInterface $options ) {
		parent::__construct( $server );
		$this->options = $options;
	}

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
				'schema' => $this->get_item_schema(),
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
			$schema = $this->get_settings_schema();
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
		return function( WP_REST_Request $request ) {
			$schema  = $this->get_settings_schema();
			$options = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
			foreach ( $schema as $key => $property ) {
				$options[ $key ] = $request->get_param( $key ) ?? $options[ $key ] ?? $property['default'] ?? null;
			}

			$this->options->update( OptionsInterface::MERCHANT_CENTER, $options );

			return [
				'status'  => 'success',
				'message' => __( 'Merchant Center Settings successfully updated.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Get the schema for request items.
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		$properties = $this->get_settings_schema();
		array_walk(
			$properties,
			function( &$value ) {
				unset(
					$value['default'],
					$value['items'],
					$value['validate_callback'],
					$value['sanitize_callback']
				);
			}
		);

		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'merchant_center_settings',
			'type'       => 'object',
			'properties' => $properties,
		];
	}

	/**
	 * Get the schema for settings endpoints.
	 *
	 * @return array
	 */
	protected function get_settings_schema(): array {
		return [
			// todo: consider including an enum of valid countries.
			'countries'               => [
				'type'              => 'array',
				'description'       => __( 'Countries in which products are available.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
				'default'           => [],
			],
			'shipping'                => [
				'type'              => 'string',
				'description'       => __(
					'Whether shipping is set up automatically by the plugin or manually in the Merchant Center.',
					'google-listings-and-ads'
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'automatic',
					'manual',
				],
			],
			'estimated_shipping_rate' => [
				'type'              => 'string',
				'description'       => __( 'Estimated flat shipping rate (USD)', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'estimated_shipping_days' => [
				'type'              => 'integer',
				'description'       => __( 'Estimated shipping time (in days).', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
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
				'description'       => __( 'Whether the checkout process is complete and secure.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => false,
			],
			'payment_methods_visible' => [
				'type'              => 'boolean',
				'description'       => __( 'Whether the payment methods are visible on the website.', 'google-listings-and-ads' ),
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
}
