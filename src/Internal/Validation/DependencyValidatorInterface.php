<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Interface DependencyValidatorInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation
 */
interface DependencyValidatorInterface {
	/**
	 * Validate dependencies that we require for the plugin to function properly.
	 *
	 * @return bool True if the dependencies are valid.
	 */
	public function validate(): bool;
}
