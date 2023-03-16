<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

/**
 * Class Dashboard
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class Dashboard implements Service, Registerable, MerchantCenterAwareInterface {
	use MenuFixesTrait;
	use MerchantCenterAwareTrait;
	use WooAdminNavigationTrait;

	/**
	 * The WC Admin page path.
	 *
	 * @var string
	 */
	public $path = '/google/dashboard';

	/**
	 * The WordPress proxy.
	 *
	 * @var WP
	 */
	protected $wp;

	/**
	 * Dashboard constructor.
	 *
	 * @param WP $wp The WordPress proxy.
	 */
	public function __construct( WP $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( ! $this->merchant_center->is_setup_complete() ) {
			add_action(
				'admin_init',
				function() {
					$this->maybe_redirect_to_start_page();
				}
			);

			// Prevent Dashboard from being registered if setup is not complete.
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
							'path'   => $this->path,
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
				'path'     => $this->path,
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
				'path'     => $this->path,
				'nav_args' => [
					'order'  => 10,
					'parent' => 'google-listings-and-ads-category',
				],
			]
		);
	}

	/**
	 * Maybe redirect to start page.
	 *
	 * @return void
	 */
	private function maybe_redirect_to_start_page(): void {
		if ( $this->wp->wp_doing_ajax() ) {
			return;
		}

		if ( ! $this->is_current_wc_admin_page( $this->path ) ) {
			return;
		}

		$this->redirect_to_start_page();
	}

	/**
	 * Redirect to start page and exit.
	 *
	 * @return void
	 */
	private function redirect_to_start_page(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart' ) );
		exit;
	}
}
