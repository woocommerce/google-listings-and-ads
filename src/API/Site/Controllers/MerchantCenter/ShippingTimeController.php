<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingTimeController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingTimeController extends BaseOptionsController {

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'mc/shipping/times',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_times_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for reading times.
	 *
	 * @return callable
	 */
	protected function get_read_times_callback(): callable {
		return function( WP_REST_Request $request ) {
			return [];
		};
	}

	/**
	 * Get the shipping times option from the options object.
	 *
	 * @return array
	 */
	protected function get_shipping_times_option(): array {
		return $this->options->get( OptionsInterface::SHIPPING_TIMES, [] );
	}
}
