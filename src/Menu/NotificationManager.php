<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\Admin\PageController;

/**
 * Class NotificationManager
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 *
 * Manages the display of a single, aggregated notification pill in the admin menu.
 * It relies on a filter to gather the total count from various contributors.
 */
class NotificationManager implements Service, Registerable {

    /**
     * Register the service, hooking into admin_menu to display notifications.
     */
    public function register(): void {
        // Hook into admin_menu with a high priority (e.g., 20) to ensure
        // all other menu items have been registered by WooCommerce and other plugins.
        add_action( 'admin_menu', [ $this, 'display_aggregated_notification_pill' ], 20 );

		// Persist the pill across menu switches between the Marketing and Analytics menus.
		add_action( 'admin_footer', [ $this, 'persist_pill'], 10 );
    }

    /**
     * Determines if the current admin page is a child page within the WooCommerce Marketing section.
     * This logic is crucial for deciding where the notification pill should be placed.
     *
     * @return bool True if the current page is a Marketing child page, false otherwise.
     */
    private function is_marketing_page() {
		global $pagenow;

		$current_page_slug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$current_page_path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';
		$current_post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

		$post_type_page = add_query_arg(
			[
				'post_type' => $current_post_type,
			],
			$pagenow
		);

		$page_path_fragment = str_replace(
			'page=',
			'',
			build_query(
				[
					'page' => $current_page_slug,
					'path' => $current_page_path,
				]
			)
		);

		$page_controller_pages = PageController::get_instance()->get_pages();
		$marketing_menu_slug   = 'woocommerce-marketing';

		$marketing_menu_pages = array_filter(
			$page_controller_pages,
			function ( $page ) use ( $marketing_menu_slug ) {
				return isset( $page['parent'] ) && $page['parent'] === $marketing_menu_slug;
			}
		);

		$is_marketing_page = false;

		foreach ( $marketing_menu_pages as $page ) {
			if ( isset( $page['path'] ) && in_array( $page['path'], [ $post_type_page, $page_path_fragment ] ) ) {
				$is_marketing_page = true;
				break;
			}
		}

		return $is_marketing_page;
	}

    /**
     * Displays an aggregated notification pill in the admin menu.
     * This method is hooked to 'admin_menu'.
     */
    public function display_aggregated_notification_pill(): void {
        global $menu, $submenu;

        // Initialize the count and apply the filter to get the total aggregated count.
        // All parts of the plugin (and other plugins) that need to add to the notification
        // should hook into this filter.
        $total_notification_count = apply_filters( 'google_for_woocommerce_admin_menu_notification_count', 0 );

        // Only proceed if there's at least one notification.
        if ( $total_notification_count > 0 ) {
            $badge_html = ' <span class="update-plugins count-' . $total_notification_count . '"><span class="update-count">' . $total_notification_count . '</span></span>';

            // Determine if the current page being loaded is within the Marketing section.
            $is_on_marketing_child_page = $this->is_marketing_page();

            if ( $is_on_marketing_child_page ) {
                // If on a Marketing child page, add the pill to the 'Google for WooCommerce' sub-menu item.
                // This means the user has the Marketing menu expanded and is viewing one of its sub-pages.
                $marketing_parent_slug           = Dashboard::MARKETING_MENU_SLUG; // Use constant for parent slug
                $google_for_woocommerce_menu_path = Dashboard::PATH; // Use constant for GfW path

                if ( isset( $submenu[ $marketing_parent_slug ] ) ) {
                    foreach ( $submenu[ $marketing_parent_slug ] as $key => $submenu_item ) {
                        // Use the submenu's slug (index 2) for robustness against translations.
                        // The slug will contain the path defined by the plugin.
                        if ( isset( $submenu_item[2] ) && strpos( $submenu_item[2], $google_for_woocommerce_menu_path ) !== false ) {
                            $submenu[ $marketing_parent_slug ][ $key ][0] .= $badge_html;
                            break;
                        }
                    }
                }
            } else {
                foreach ( $menu as $key => $menu_item ) {
                    // Use the top-level menu's slug (index 2) for robustness against translations.
                    if ( isset( $menu_item[2] ) && Dashboard::MARKETING_MENU_SLUG === $menu_item[2] ) {
                        $menu[ $key ][0] .= $badge_html;
                        break;
                    }
                }
            }
        }
    }

	/**
	 * Determines if the current admin page is the Analytics.
	 *
	 * @return bool True if the current menu item is the Analytics menu or one of it's sub menus.
	 */
	private function is_analytics_page(): bool {
		$current_page_slug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $current_page_slug !== 'wc-admin' ) {
			return false;
		}

		$current_page_path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';
		$parts = explode('/', ltrim($current_page_path, '/'));

		if ( isset( $parts[0] ) && $parts[0] === 'analytics' ) {
			return true;
		}

		return false;
	}

	/**
	 * Persist the pill in the correct location when switching between the Marketing/Analytics menus.
	 */
	public function persist_pill(): void {
		if ( ! $this->is_marketing_page() && ! $this->is_analytics_page() ) {
			return;
		}
		?>
		<script>
			(function() {
				const marketingMenu = document.getElementById('toplevel_page_woocommerce-marketing');
				const topMenu = document.querySelector('.toplevel_page_woocommerce-marketing > a > .wp-menu-name');
				const badge = document.querySelector('#toplevel_page_woocommerce-marketing .update-plugins');

				const observer = new MutationObserver(function() {
					if (marketingMenu.classList.contains('wp-has-current-submenu')) {
						const subMenu = document.querySelector('[href="admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard"]');

						if (subMenu && !subMenu.contains(badge)) {
							// Ensure there is white space betweem the bade and menu title for visual consistency.
							subMenu.textContent.trimEnd();
							subMenu.textContent += ' ';

							// Move the bade to the correct location.
							subMenu.appendChild(badge);
						}
					} else {
						if (topMenu && !topMenu.contains(badge)) {
							// Ensure there is white space betweem the bade and menu title for visual consistency.
							topMenu.textContent.trimEnd();
							topMenu.textContent += ' ';

							// Move the bade to the correct location.
							topMenu.appendChild(badge);
						}
					}
				});

				observer.observe(marketingMenu, { attributes: true, attributeFilter: ['class'] });
			})();
		</script>
		<?php
	}
}
