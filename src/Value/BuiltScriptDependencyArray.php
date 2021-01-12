<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Class BuiltScriptDependencyArray
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class BuiltScriptDependencyArray extends ArrayWithRequiredKeys implements ValueInterface {

	/**
	 * Array of required keys. Should be in key => value format.
	 *
	 * @var array
	 */
	protected $required_keys = [
		'version'      => true,
		'dependencies' => true,
	];

	/**
	 * Get the value of the object.
	 *
	 * @return array
	 */
	public function get(): array {
		return $this->data;
	}

	/**
	 * Get the version from the dependency array.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->data['version'];
	}

	/**
	 * Get the array of dependencies from the dependency array.
	 *
	 * @return array
	 */
	public function get_dependencies(): array {
		return $this->data['dependencies'];
	}
}
