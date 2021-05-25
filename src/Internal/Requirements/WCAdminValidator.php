<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExtensionRequirementException;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCAdminValidator
 *
 * @package AutomatticWooCommerceGoogleListingsAndAdsInternalRequirements
 */
class WCAdminValidator extends RequirementValidator {

	/**
	 * Validate all requirements for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_wc_admin_active();
			return true;
		} catch ( ExtensionRequirementException $e ) {
			$this->add_admin_notice( $e );
			return false;
		}
	}

	/**
	 * Validate that WooCommerce Admin is enabled.
	 *
	 * @throws ExtensionRequirementException When the WooCommerce Admin is disabled by hook.
	 */
	protected function validate_wc_admin_active() {
		if ( apply_filters( 'woocommerce_admin_disabled', false ) ) {
			throw ExtensionRequirementException::missing_required_plugin( 'WooCommerce Admin' );
		}
	}
}
