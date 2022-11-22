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
class AttributeMapping implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'    => __( 'Attribute Mapping', 'google-listings-and-ads' ),
						'parent'   => 'google-listings-and-ads-category',
						'path'     => '/google/attribute-mapping',
						'id'       => 'google-attribute-mapping',
						'nav_args' => [
							'order'  => 40,
							'parent' => 'google-listings-and-ads-category',
						],
					]
				);
			}
		);
	}
}
