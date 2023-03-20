<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

/**
 * Class GetStarted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class GetStarted implements Service, Registerable, MerchantCenterAwareInterface {

	use MenuFixesTrait;
	use MerchantCenterAwareTrait;
	use WooAdminNavigationTrait;

	/**
	 * The WC Admin page path.
	 *
	 * @var string
	 */
	public static $path = '/google/start';

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
		if ( $this->merchant_center->is_setup_complete() ) {
			add_action(
				'admin_init',
				[ $this, 'maybe_redirect_to_dashboard' ]
			);

			// Prevent Start page from being registered if setup is complete.
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
							'path'   => self::$path,
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
				'id'       => 'google-start',
				'title'    => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
				'parent'   => 'woocommerce',
				'path'     => self::$path,
				'nav_args' => [
					'title'        => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
					'is_category'  => false,
					'menuId'       => 'plugins',
					'is_top_level' => true,
				],
			]
		);
	}

	/**
	 * Maybe redirect to dashboard.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_dashboard(): void {
		if ( $this->wp->wp_doing_ajax() ) {
			return;
		}

		if ( ! $this->is_current_wc_admin_page( self::$path ) ) {
			return;
		}

		$this->redirect_to_dashboard();
	}

	/**
	 * Redirect to start page and exit.
	 *
	 * @return void
	 */
	private function redirect_to_dashboard(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&path=' . rawurlencode( Dashboard::$path ) ) );
		exit;
	}
}
