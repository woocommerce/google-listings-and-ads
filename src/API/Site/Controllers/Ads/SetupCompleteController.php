<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class SetupCompleteController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class SetupCompleteController extends BaseController {

	use EmptySchemaPropertiesTrait;

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$this->register_route(
			'ads/setup/complete',
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
	 * Get the callback function for marking setup complete.
	 *
	 * @return callable
	 */
	protected function get_setup_complete_callback(): callable {
		return function ( Request $request ) {
			do_action( 'woocommerce_gla_ads_setup_completed' );

			return new Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully marked Ads setup as completed.', 'google-listings-and-ads' ),
				]
			);
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
		return 'ads_setup_complete';
	}
}
