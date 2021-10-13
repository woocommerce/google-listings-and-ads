<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class Settings
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class Settings implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'    => __( 'Settings', 'google-listings-and-ads' ),
						'parent'   => 'google-listings-and-ads-category',
						'path'     => '/google/settings',
						'id'       => 'google-settings',
						'nav_args' => [
							'order'  => 40,
							'parent' => 'google-listings-and-ads-category',
							// Highlight this menu item for other subpages.
							'matchExpression' => '/google/settings(/[^/]+)?',
						],
					]
				);
				// TODO:  Check if we can make it a bit DRYier, and reduce the below, to smth like `'path' => '/google/settings/:subpage?'`
				// So far it's hard to guess the type of register_page options,
				// which is documented to be the type of "Options for PageController::register_page()." ➰
				wc_admin_register_page(
					[
						'title'  => __( 'Settings', 'google-listings-and-ads' ),
						'parent' => 'google-listings-and-ads-category',
						'path'   => '/google/settings/edit-phone-number',
						'id'     => 'google-settings-phone-number',
					]
				);
				wc_admin_register_page(
					[
						'title'  => __( 'Settings', 'google-listings-and-ads' ),
						'parent' => 'google-listings-and-ads-category',
						'path'   => '/google/settings/edit-store-address',
						'id'     => 'google-settings-store-address',
					]
				);
				wc_admin_register_page(
					[
						'title'  => __( 'Settings', 'google-listings-and-ads' ),
						'parent' => 'google-listings-and-ads-category',
						'path'   => '/google/settings/reconnect-accounts',
						'id'     => 'google-settings-reconnect-accounts',
					]
				);
			}
		);
	}
}
