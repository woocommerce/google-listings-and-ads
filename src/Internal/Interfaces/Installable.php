<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface Installable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces
 */
interface Installable {

	/**
	 * Run installation logic for this class.
	 */
	public function install(): void;
}
