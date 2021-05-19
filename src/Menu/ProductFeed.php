<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class ProductFeed
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class ProductFeed implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'    => __( 'Product Feed', 'google-listings-and-ads' ),
						'parent'   => 'google-listings-and-ads-category',
						'path'     => '/google/product-feed',
						'id'       => 'google-product-feed',
						'nav_args' => [
							'order'  => 30,
							'parent' => 'google-listings-and-ads-category',
						],
					]
				);
			}
		);
	}
}
