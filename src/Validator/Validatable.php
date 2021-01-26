<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;

defined( 'ABSPATH' ) || exit;

/**
 * Interface Validatable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Validator
 */
interface Validatable {
	/**
	 * @param ClassMetadata $metadata
	 */
	public static function load_validator_metadata( ClassMetadata $metadata );
}
