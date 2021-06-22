<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidVersion;

defined( 'ABSPATH' ) || exit;

/**
 * Class VersionValidator
 *
 * @package AutomatticWooCommerceGoogleListingsAndAdsInternalRequirements
 */
class VersionValidator extends RequirementValidator {

	/**
	 * Validate all requirements for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_php_version();
			return true;
		} catch ( InvalidVersion $e ) {
			$this->add_admin_notice( $e );
			return false;
		}
	}

	/**
	 * Validate the PHP version being used.
	 *
	 * @throws InvalidVersion When the PHP version does not meet the minimum version.
	 */
	protected function validate_php_version() {
		if ( ! version_compare( PHP_VERSION, WC_GLA_MIN_PHP_VER, '>=' ) ) {
			throw InvalidVersion::from_requirement( 'PHP', PHP_VERSION, WC_GLA_MIN_PHP_VER );
		}
	}
}
