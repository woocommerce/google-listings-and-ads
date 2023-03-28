<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\Admin\PageController;

/**
 * Trait MenuFixesTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
trait MenuFixesTrait {
	/**
	 * Register a React-powered page to the classic submenu of wc-admin.
	 *
	 * To ensure the order of this plugin will be below the Coupons in the Marketing menu of
	 * WC admin page, here needs a workaround due to several different causes.
	 *
	 * TL;DR
	 * - `wc_admin_register_page()` doesn't pass the `position` option to `add_submenu_page`.
	 * - `PageController->register_page()` called by `wc_admin_register_page` replaces the
	 *   menu/submenu slug internally.
	 * - Coupons submenu is added by `register_post_type` that calls `add_submenu_page`
	 *   directly in WP core and is moved to Marketing dynamically.
	 *
	 * Details:
	 *
	 * There is a guide with a few examples showing how to add a page to WooCommerce Admin.
	 *
	 * @link https://developer.woocommerce.com/extension-developer-guide/working-with-woocommerce-admin-pages/
	 *
	 * Originally, a React-powered page is expected to be registered by WC core function
	 * `wc_admin_register_page`, and the function also handles the registration of wp-admin
	 * menu and submenu via PageController. In addition, the function will concatenate
	 * 'wc-admin&path=' with the page path as the menu/submenu slug when calling `add_menu_page`
	 * or `add_submenu_page`.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Admin/PageController.php#L449-L451
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Admin/PageController.php#L458
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Admin/PageController.php#L467
	 *
	 * However, the main menu of Marketing is not registered in that way but calls WP core
	 * function `add_menu_page` directly with 'woocommerce-marketing' as its menu slug,
	 * so the menu slug of Marketing won't have the above replacement processing.
	 * This causes other pages that need to be submenus under Marketing won't appear
	 * if they were added by `wc_admin_register_page` due to mismatched slugs.
	 * Instead, they have to be added via "woocommerce_marketing_menu_items" filter.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Internal/Admin/Marketing.php#L71-L92
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Internal/Admin/Marketing.php#L106-L119
	 *
	 * Unfortunately, `wc_admin_register_page` doesn't pass the `position` option to
	 * `add_submenu_page`. So the order of submenus is determined with the calling order
	 * of `wc_admin_register_page`, which usually is decided by the `priority` parameter
	 * of the corresponding "admin_menu" action. Even though raising the priority of
	 * "admin_menu" action to 6 or a smaller number could make submenu appear but it will
	 * still be above the Coupons or even the Overview submenu. As mentioned at the beginning,
	 * specifying the `position` won't work either because it won't be passed to `add_submenu_page`.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Admin/PageController.php#L466-L473
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Internal/Admin/Marketing.php#L60
	 * @link https://github.com/WordPress/wordpress-develop/blob/6.0.3/src/wp-admin/includes/plugin.php#L1445-L1449
	 *
	 * About the Coupons submenu, it's added by `register_post_type` in an "init" action,
	 * and its appearing menu might be modified to Marketing dynamically, then WP core calls
	 * `add_submenu_page` directly via "admin_menu" action with the default priority 10.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/includes/class-wc-post-types.php#L451-L491
	 * @link https://github.com/woocommerce/woocommerce/blob/7.0.0/plugins/woocommerce/src/Internal/Admin/Coupons.php#L95-L98
	 * @link https://github.com/WordPress/wordpress-develop/blob/6.0.3/src/wp-includes/post.php#L2067-L2076
	 * @link https://github.com/WordPress/wordpress-develop/blob/6.0.3/src/wp-includes/default-filters.php#L537
	 *
	 * Taken together, when using the suggested `wc_admin_register_page` to add a submenu
	 * under Marketing menu, if the priority of "admin_menu" action is > 6, it won't appear.
	 * If the priority is <= 6, the order of added submenu will be above the Coupons.
	 * When using the dedicated "woocommerce_marketing_menu_items" filter, the order of added
	 * submenu will still be above the Coupons.
	 *
	 * In summary, the order in which submenus call `add_submenu_page` determines the order
	 * in which they appear in the Marketing menu, and the way in which submenus call
	 * `add_submenu_page` and whether they are called before the Marketing menu calls
	 * `add_menu_page` determines whether the submenus can match the parent slug to appear
	 * under Marketing menu.
	 *
	 * The method and order of calling is as follows:
	 * 1. Overview submenu: PageController->register_page() with priority 5.
	 * 2. Marketing menu: add_menu_page() with priority 6.
	 * 3. Coupons submenu: add_submenu_page() with the default priority 10.
	 * 4. This workaround will be this order if we add a submenu by "admin_menu"
	 *    action with a priority >= 10. Moreover, the `position` will be effective
	 *    to change the final ordering.
	 *
	 * @param array $options {
	 *   Array describing the submenu page.
	 *
	 *   @type string id            ID to reference the page.
	 *   @type string title         Page title. Used in menus and breadcrumbs.
	 *   @type string parent        Parent ID.
	 *   @type string path          Path for this page.
	 *   @type string capability    Capability needed to access the page.
	 *   @type int    position|null Menu item position.
	 * }
	 */
	protected function register_classic_submenu_page( $options ) {
		$defaults = [
			'capability' => 'manage_woocommerce',
			'position'   => null,
		];

		$options            = wp_parse_args( $options, $defaults );
		$options['js_page'] = true;

		if ( 0 !== strpos( $options['path'], PageController::PAGE_ROOT ) ) {
			$options['path'] = PageController::PAGE_ROOT . '&path=' . $options['path'];
		}

		add_submenu_page(
			$options['parent'],
			$options['title'],
			$options['title'],
			$options['capability'],
			$options['path'],
			[ PageController::class, 'page_wrapper' ],
			$options['position'],
		);

		PageController::get_instance()->connect_page( $options );
	}
}
