<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;

/**
 * Class Dashboard
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class Dashboard implements Service, Registerable, MerchantCenterAwareInterface {

	use MenuFixesTrait;
	use MerchantCenterAwareTrait;
	use WooAdminNavigationTrait;

	public const PATH = '/google/dashboard';

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( ! $this->merchant_center->is_setup_complete() ) {
			return;
		}

		add_action(
			'admin_menu',
			function() {
				if ( $this->is_woo_nav_enabled() ) {
					$this->register_navigation_pages();
				} else {
					$this->register_classic_submenu_page(
						[
							'id'     => 'google-listings-and-ads',
							'title'  => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
							'parent' => 'woocommerce-marketing',
							'path'   => self::PATH,
						]
					);
				}
			}
		);
	}

	/**
	 * Register navigation pages for WC Navigation.
	 */
	protected function register_navigation_pages(): void {
		wc_admin_register_page(
			[
				'id'       => 'google-listings-and-ads-category',
				'title'    => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
				'parent'   => 'woocommerce',
				'path'     => self::PATH,
				'nav_args' => [
					'title'        => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
					'is_category'  => true,
					'menuId'       => 'plugins',
					'is_top_level' => true,
				],
			]
		);

		wc_admin_register_page(
			[
				'id'       => 'google-dashboard',
				'title'    => __( 'Dashboard', 'google-listings-and-ads' ),
				'parent'   => 'google-listings-and-ads-category',
				'path'     => self::PATH,
				'nav_args' => [
					'order'  => 10,
					'parent' => 'google-listings-and-ads-category',
				],
			]
		);
	}
}
