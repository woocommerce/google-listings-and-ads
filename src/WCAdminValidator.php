<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCAdminValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
class WCAdminValidator extends DependencyValidator {

	/**
	 * Execute all validation methods.
	 */
	protected function validate_all(): void {
		$this->validate_wc_admin_active();
	}

	/**
	 * Validate that WooCommerce Admin is enabled.
	 *
	 * @throws RuntimeException When the WooCommerce Admin is disabled by hook.
	 */
	protected function validate_wc_admin_active() {
		if ( apply_filters( 'woocommerce_admin_disabled', false ) ) {
			throw new RuntimeException(
				__( 'Google Listings and Ads requires WooCommerce Admin to be enabled.', 'google-listings-and-ads' )
			);
		}
	}
}
