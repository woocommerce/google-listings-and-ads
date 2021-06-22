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
			$this->validate_wp_version();
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
		if ( ! version_compare( PHP_VERSION, '7.3', '>=' ) ) {
			throw InvalidVersion::from_requirement( 'PHP', PHP_VERSION, '7.3' );
		}
	}

	/**
	 * Validate WordPress version being used.
	 *
	 * @throws InvalidVersion When the WordPress version does not meet the minimum version.
	 */
	protected function validate_wp_version() {
		$wp_version = get_bloginfo( 'version' );
		if ( ! version_compare( $wp_version, '5.5', '>=' ) ) {
			throw InvalidVersion::from_requirement( 'WordPress', $wp_version, '5.5' );
		}
	}
}
