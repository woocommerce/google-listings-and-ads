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
