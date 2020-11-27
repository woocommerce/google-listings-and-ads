<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

/**
 * Trait MenuFixesTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
trait MenuFixesTrait {

	/**
	 * Fix sub-menu paths. wc_admin_register_page() gets it wrong.
	 */
	protected function fix_menu_paths() {
		global $submenu;

		if ( ! isset( $submenu['woocommerce-marketing'] ) ) {
			return;
		}

		foreach ( $submenu['woocommerce-marketing'] as &$item ) {
			// The "slug" (aka the path) is the third item in the array.
			if ( 0 === strpos( $item[2], 'wc-admin' ) ) {
				$item[2] = 'admin.php?page=' . $item[2];
			}
		}
	}
}
