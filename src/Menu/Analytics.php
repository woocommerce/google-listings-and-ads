<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class Analytics
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class Analytics implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'  => __( 'Analytics', 'google-listings-and-ads' ),
						'parent' => 'woocommerce-marketing',
						'path'   => '/google/analytics',
						'id'     => 'google-analytics',
					]
				);
			}
		);
	}
}
