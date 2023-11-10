<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Trait BatchShippingTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
trait BatchShippingTrait {
	/**
	 * Get the callback for deleting shipping items via batch.
	 *
	 * @return callable
	 */
	protected function get_batch_delete_shipping_callback(): callable {
		return function ( Request $request ) {
			$country_codes = $request->get_param( 'country_codes' );

			$responses = [];
			$errors    = [];
			foreach ( $country_codes as $country_code ) {
				$route          = "/{$this->get_namespace()}/{$this->route_base}/{$country_code}";
				$delete_request = new Request( 'DELETE', $route );

				$response = $this->server->dispatch_request( $delete_request );
				if ( 200 !== $response->get_status() ) {
					$errors[] = $response->get_data();
				} else {
					$responses[] = $response->get_data();
				}
			}

			return new Response(
				[
					'errors'  => $errors,
					'success' => $responses,
				],
			);
		};
	}
}
