<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidVersion;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCValidator
 *
 * @package AutomatticWooCommerceGoogleListingsAndAdsInternalRequirements
 */
class WCValidator extends RequirementValidator {

	/**
	 * Validate all requirements for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_wc_version();
			return true;
		} catch ( InvalidVersion $e ) {
			$this->add_admin_notice( $e );
			return false;
		}
	}

	/**
	 * Validate the minimum required WooCommerce version (after plugins are fully loaded).
	 *
	 * @throws InvalidVersion When the WooCommerce version does not meet the minimum version.
	 */
	protected function validate_wc_version() {
		if ( ! version_compare( WC_VERSION, WC_GLA_MIN_WC_VER, '>=' ) ) {
			throw InvalidVersion::from_requirement( 'WooCommerce', WC_VERSION, WC_GLA_MIN_WC_VER );
		}
	}
}
