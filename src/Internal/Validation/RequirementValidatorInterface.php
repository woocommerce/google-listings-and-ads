<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Interface RequirementValidatorInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation
 */
interface RequirementValidatorInterface {
	/**
	 * Validate requirements for plugin to function properly.
	 *
	 * @return bool True if the requirements are met.
	 */
	public function validate(): bool;
}
