<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

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
				if ( ! Features::is_enabled( 'navigation' ) ) {
					$this->fix_menu_paths();
				} else {
					wc_admin_register_page(
						[
							'id'       => 'google-start',
							'title'    => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
							'parent'   => 'woocommerce',
							'path'     => '/google/start',
							'nav_args' => [
								'title'        => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
								'is_category'  => false,
								'menuId'       => 'plugins',
								'is_top_level' => true,
							],
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
		if ( ! Features::is_enabled( 'navigation' ) ) {
			$items[] = [
				'id'         => 'google-start',
				'title'      => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
				'path'       => '/google/start',
				'capability' => 'manage_woocommerce',
			];
		}

		return $items;
	}
}
