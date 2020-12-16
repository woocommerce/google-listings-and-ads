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
					[
						'title'  => __( 'MC Setup Wizard', 'google-listings-and-ads' ),
						'parent' => '',
						'path'   => '/google/setup-mc',
						'id'     => 'google-setup-mc',
					]
				);
			}
		);
	}
}
