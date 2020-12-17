<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingRateController extends BaseOptionsController {

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		// TODO: Implement register_routes() method.
	}

	/**
	 * @return array
	 */
	protected function get_settings_schema(): array {
		return [
			'rates'     => [
				'type'  => 'array',
				'items' => [
					'type' => 'string',
				],
			],
			// todo: consider including an enum of valid countries.
			'countries' => [
				'type'              => 'array',
				'description'       => __( 'Countries in which products are available.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
				'default'           => [],
			],
			'rate',
		];
	}
}
