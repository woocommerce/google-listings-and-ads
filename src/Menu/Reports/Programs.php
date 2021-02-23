<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu\Reports;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class Programs
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu\Reports
 */
class Programs implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'  => __( 'Programs Report', 'google-listings-and-ads' ),
						'parent' => 'woocommerce-marketing',
						'path'   => '/google/reports/programs',
						'id'     => 'google-reports-programs',
					]
				);
			}
		);
	}
}
