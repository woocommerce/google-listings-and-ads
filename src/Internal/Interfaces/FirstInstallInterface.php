<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces;

/**
 * Interface FirstInstallInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces
 */
interface FirstInstallInterface {

	/**
	 * Logic to run when the plugin is first installed.
	 */
	public function first_install(): void;
}
