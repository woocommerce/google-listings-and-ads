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
class ComponentTest implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'  => __( 'Component Test', 'google-listings-and-ads' ),
						'parent' => 'google-listings-and-ads-category',
						'path'   => '/google/component-test',
						'id'     => 'google-component-test',
					]
				);
			}
		);
	}
}
