<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidVersion;

defined( 'ABSPATH' ) || exit;

/**
 * Class VersionValidator. Validates PHP Requirements like the version and the architecture.
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
			$this->validate_php_architecture();
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

	/**
	 * Validate the PHP Architecture being 64 Bits.
	 * This is done by checking PHP_INT_SIZE. In 32 bits this will be 4 Bytes. In 64 Bits this will be 8 Bytes
	 *
	 * @see https://www.php.net/manual/en/language.types.integer.php
	 * @since 2.3.9
	 *
	 * @throws InvalidVersion When the PHP Architecture is not 64 Bits.
	 */
	protected function validate_php_architecture() {
		if ( PHP_INT_SIZE !== 8 ) {
			throw InvalidVersion::invalid_architecture();
		}
	}
}
