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
	 */
	public function install(): void;
}
