<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCAdminValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation
 */
class WCAdminValidator extends DependencyValidator {

	/**
	 * Validate all dependencies that we require for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_wc_admin_active();
			return true;
		} catch ( RuntimeException $e ) {
			$this->add_admin_notice( $e );
			return false;
		}
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
