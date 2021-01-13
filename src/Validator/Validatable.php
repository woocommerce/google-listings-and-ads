<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;

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
