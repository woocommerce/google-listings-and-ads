<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MobileTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API
 */
trait MobileTrait {

	/**
	 * Check if the request is from WooCommerce iOS app.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function is_wc_ios( WP_REST_Request $request ) {
		return $request->get_header( 'User-Agent' ) && stristr( $request->get_header( 'User-Agent' ), 'wc-ios' );
	}

	/**
	 * Check if the request is from WooCommerce Android app.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function is_wc_android( WP_REST_Request $request ) {
		return $request->get_header( 'User-Agent' ) && stristr( $request->get_header( 'User-Agent' ), 'wc-android' );
	}

	/**
	 * Check if the request is from WooCommerce mobile app.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function is_wc_mobile_app( WP_REST_Request $request ) {
		return $this->is_wc_ios( $request ) || $this->is_wc_android( $request );
	}
}
