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
							'order'  => 50,
							'parent' => 'google-listings-and-ads-category',
						],
					]
				);
			}
		);
	}
}
