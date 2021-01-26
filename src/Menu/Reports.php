<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class Reports
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class Reports implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'  => __( 'Reports', 'google-listings-and-ads' ),
						'parent' => 'woocommerce-marketing',
						'path'   => '/google/reports',
						'id'     => 'google-reports',
					]
				);
			}
		);
	}
}
