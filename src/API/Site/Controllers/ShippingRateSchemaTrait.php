<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ShippingRateSchemaTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 *
 * @since 1.12.0
 */
trait ShippingRateSchemaTrait {

	use CountryCodeTrait;

	/**
	 * @return array
	 */
	protected function get_shipping_rate_schema(): array {
		return [
			'id'       => [
				'type'        => 'number',
				'description' => __( 'The shipping rate unique identification number.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'country'  => [
				'type'              => 'string',
				'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'required'          => true,
			],
			'method'   => [
				'type'              => 'string',
				'description'       => __( 'The shipping method.', 'google-listings-and-ads' ),
				'enum'              => [
					ShippingZone::METHOD_FLAT_RATE,
				],
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'currency' => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => 'USD', // todo: default to store currency.
			],
			'rate'     => [
				'type'              => 'number',
				'minimum'           => 0,
				'description'       => __( 'The shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'options'  => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'description'          => __( 'Array of options for the shipping method.', 'google-listings-and-ads' ),
				'context'              => [ 'view', 'edit' ],
				'validate_callback'    => 'rest_validate_request_arg',
				'default'              => [],
				'properties'           => [
					'free_shipping_threshold' => [
						'type'              => 'number',
						'minimum'           => 0,
						'description'       => __( 'Minimum price eligible for free shipping.', 'google-listings-and-ads' ),
						'context'           => [ 'view', 'edit' ],
						'validate_callback' => 'rest_validate_request_arg',
					],
				],
			],
		];
	}
}
