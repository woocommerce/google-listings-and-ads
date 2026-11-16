<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MerchantCenterRequiredResponseTrait
 *
 * Builds the error response for a request that asks for the product advertising source
 * while no Merchant Center account is connected, so every endpoint returns the same code and message.
 *
 * @since 3.11.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
trait MerchantCenterRequiredResponseTrait {

	/**
	 * Get the response for a product advertising source without a connected Merchant Center account.
	 *
	 * @since 3.11.0
	 *
	 * @return Response
	 */
	protected function get_merchant_center_required_response(): Response {
		return new Response(
			[
				'code'    => 'merchant_center_required',
				'message' => __( 'A campaign that advertises products requires a connected Merchant Center account.', 'google-listings-and-ads' ),
			],
			400
		);
	}
}
