<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu\Reports;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class Products
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu\Reports
 */
class Products implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'    => __( 'Products Report', 'google-listings-and-ads' ),
						'parent'   => 'google-reports',
						'path'     => '/google/reports/products',
						'id'       => 'google-reports-products',
						'nav_args' => [
							'order'  => 20,
							'parent' => 'google-reports',
						],
					]
				);
			}
		);
	}
}
