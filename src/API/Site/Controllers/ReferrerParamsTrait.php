<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ReferrerParamsTrait
 *
 * Shared handling for the `referrer_type`/`referrer_id` params used to preserve
 * attribution across OAuth redirects.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
trait ReferrerParamsTrait {

	/**
	 * Get the shared query param schema for referrer_type/referrer_id.
	 *
	 * @return array
	 */
	protected function get_referrer_params(): array {
		return [
			'referrer_type' => [
				'description'       => __( 'Indicates the type of referrer that initiated this connection, to preserve attribution across the OAuth redirect.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'referrer_id'   => [
				'description'       => __( 'Indicates the ID of the referrer that initiated this connection, to preserve attribution across the OAuth redirect.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Append the referrer_type/referrer_id params from the request onto a URL, if present.
	 *
	 * @param string  $url
	 * @param Request $request
	 *
	 * @return string
	 */
	protected function append_referrer_args( string $url, Request $request ): string {
		$referrer_args = array_filter(
			[
				'referrer_type' => $request->get_param( 'referrer_type' ),
				'referrer_id'   => $request->get_param( 'referrer_id' ),
			]
		);

		if ( empty( $referrer_args ) ) {
			return $url;
		}

		return add_query_arg( $referrer_args, $url );
	}
}
