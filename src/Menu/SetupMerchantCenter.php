<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class SetupMerchantCenter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class SetupMerchantCenter implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					array(
						'title'  => __( 'MC Setup Wizard', 'woocommerce-admin' ),
						'parent' => '',
						'path'   => '/setup-mc',
						'id'     => 'setups-mc',
					)
				);
			}
		);
	}
}
