<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidVersion;

defined( 'ABSPATH' ) || exit;

/**
 * Class VersionValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
class VersionValidator extends DependencyValidator {

	/**
	 * Execute all validation methods.
	 */
	protected function validate_all(): void {
		$this->validate_php_version();
	}

	/**
	 * Validate the PHP version being used.
	 *
	 * @throws InvalidVersion When the PHP version does not meet the minimum version.
	 */
	protected function validate_php_version() {
		if ( ! version_compare( PHP_VERSION, '7.3', '>=' ) ) {
			throw InvalidVersion::from_php_version( PHP_VERSION, '7.3' );
		}
	}
}
