<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface Installable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces
 */
interface InstallableInterface {

	/**
	 * Run installation logic for this class.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void;
}
