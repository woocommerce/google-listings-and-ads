<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Trait AdminAssetHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
trait AdminAssetHelper {
	/**
	 * Get the enqueue action to use.
	 *
	 * @return string
	 */
	protected function get_enqueue_action(): string {
		return 'admin_enqueue_scripts';
	}

	/**
	 * Get the dequeue action to use.
	 *
	 * @return string
	 */
	protected function get_dequeue_action(): string {
		return 'admin_print_scripts';
	}
}
