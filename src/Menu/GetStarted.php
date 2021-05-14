<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;

/**
 * Class GetStarted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class GetStarted implements Service, Registerable, MerchantCenterAwareInterface {

	use MenuFixesTrait;
	use MerchantCenterAwareTrait;

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( $this->merchant_center->is_setup_complete() ) {
			return;
		}

		add_filter(
			'woocommerce_marketing_menu_items',
			function( $menu_items ) {
				return $this->add_items( $menu_items );
			}
		);

		add_action(
			'admin_menu',
			function() {
				$this->fix_menu_paths();

				if (
					method_exists( Menu::class, 'add_plugin_item' ) &&
					Features::is_enabled( 'navigation' )
				) {
					Menu::add_plugin_item(
						[
							'id'         => 'google-start',
							'title'      => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
							'capability' => 'manage_woocommerce',
							'url'        => 'wc-admin&path=/google/start',
						]
					);
				}
			}
		);
	}

	/**
	 * Add Google Menu item under Marketing
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	protected function add_items( array $items ): array {
		$items[] = [
			'id'         => 'google-start',
			'title'      => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
			'path'       => '/google/start',
			'capability' => 'manage_woocommerce',
		];

		return $items;
	}
}
