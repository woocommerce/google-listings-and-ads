<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;

/**
 * Class NotificationManager
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 *
 * Manages the display of a single, aggregated notification pill in the admin menu.
 * It relies on a filter to gather the total count from various contributors.
 */
class NotificationManager implements ContainerAwareInterface, Service, Registerable {

	use PluginHelper;
	use ContainerAwareTrait;

	/**
	 * @var AssetsHandlerInterface
	 */
	protected $assets_handler;

	/**
	 * NotificationManager constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 */
	public function __construct( AssetsHandlerInterface $assets_handler ) {
		$this->assets_handler = $assets_handler;
	}

	/**
	 * Register the service, hooking into admin_menu to display notifications.
	 */
	public function register(): void {
		// Hook into admin_menu with a high priority (e.g., 20) to ensure
		// all other menu items have been registered by WooCommerce and other plugins.
		add_action( 'admin_menu', [ $this, 'display_aggregated_notification_pill' ], 20 );
	}

	/**
	 * Register assets.
	 *
	 * @return void
	 */
	private function register_assets(): void {
		$notification_manager = new AdminScriptWithBuiltDependenciesAsset(
			'notification-manager',
			'js/build/notification-manager',
			"{$this->get_root_dir()}/js/build/notification-manager.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => $this->get_version(),
				]
			),
			function () {
				return PageController::is_admin_page();
			}
		);

		$this->assets_handler->register( $notification_manager );

		add_action(
			'admin_enqueue_scripts',
			function () use ( $notification_manager ) {
				if ( ! $this->is_marketing_page() && ! $this->is_analytics_page() ) {
					return;
				}

				$this->assets_handler->enqueue( $notification_manager );
			}
		);
	}

	/**
	 * Determines if the current admin page is a child page within the WooCommerce Marketing section.
	 * This logic is crucial for deciding where the notification pill should be placed.
	 *
	 * @return bool True if the current page is a Marketing child page, false otherwise.
	 */
	private function is_marketing_page(): bool {
		global $pagenow;

		$current_page_slug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$current_page_path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$current_post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

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
		$marketing_menu_slug   = Dashboard::MARKETING_MENU_SLUG;

		$marketing_menu_pages = array_filter(
			$page_controller_pages,
			static function ( $page ) use ( $marketing_menu_slug ) {
				return isset( $page['parent'] ) && $page['parent'] === $marketing_menu_slug;
			}
		);

		$is_marketing_page = false;

		foreach ( $marketing_menu_pages as $page ) {
			if ( isset( $page['path'] ) && in_array( $page['path'], [ $post_type_page, $page_path_fragment ], true ) ) {
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
		$total_notification_count = apply_filters( 'google_for_woocommerce_admin_menu_notification_count', $this->initial_notification_count() );

		// Only proceed if there's at least one notification.
		if ( $total_notification_count > 0 ) {
			// Register assets.
			$this->register_assets();

			$badge_html = ' <span class="update-plugins count-' . $total_notification_count . '"><span class="update-count">' . $total_notification_count . '</span></span>';

			// Determine if the current page being loaded is within the Marketing section.
			$is_on_marketing_child_page = $this->is_marketing_page();

			if ( $is_on_marketing_child_page ) {
				// If on a Marketing child page, add the pill to the 'Google for WooCommerce' sub-menu item.
				// This means the user has the Marketing menu expanded and is viewing one of its sub-pages.
				$marketing_parent_slug            = Dashboard::MARKETING_MENU_SLUG; // Use constant for parent slug
				$google_for_woocommerce_menu_path = Dashboard::PATH; // Use constant for GfW path

				if ( isset( $submenu[ $marketing_parent_slug ] ) ) {
					foreach ( $submenu[ $marketing_parent_slug ] as $key => $submenu_item ) {
						// Use the submenu's slug (index 2) for robustness against translations.
						// The slug will contain the path defined by the plugin.
						if ( isset( $submenu_item[2] ) && strpos( $submenu_item[2], $google_for_woocommerce_menu_path ) !== false ) {
							$submenu[ $marketing_parent_slug ][ $key ][0] .= $badge_html; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							break;
						}
					}
				}
			} else {
				foreach ( $menu as $key => $menu_item ) {
					// Use the top-level menu's slug (index 2) for robustness against translations.
					if ( isset( $menu_item[2] ) && Dashboard::MARKETING_MENU_SLUG === $menu_item[2] ) {
						$menu[ $key ][0] .= $badge_html; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						break;
					}
				}
			}
		}
	}

	/**
	 * Returns the initial notification count for the admin menu.
	 *
	 * @return int The updated notification count, which is either 1 (if there are recommendations) or 0 (if there are no recommendations).
	 */
	public function initial_notification_count(): int {
		global $wpdb;
		$count = 0;

		$query           = $this->container->get( AdsRecommendationsService::class );
		$recommendations = $query->get_recommendations();

		// Return early if there are no recommendations.
		if ( empty( $recommendations ) ) {
			return $count;
		}

		// Check recommendation dates and user preference.
		$preferences = get_user_meta( get_current_user_id(), "{$wpdb->prefix}persisted_preferences", true );

		// If the user has not interacted with a recommendation yet.
		if ( ! is_array( $preferences ) || ! isset( $preferences['woocommerce/google-listings-and-ads']['pmax-improve-assets-banner']['actionType'] ) || ! isset( $preferences['woocommerce/google-listings-and-ads']['pmax-improve-assets-banner']['actionTime'] ) ) {
			return ++$count;
		}

		$action_time = $preferences['woocommerce/google-listings-and-ads']['pmax-improve-assets-banner']['actionTime'];

		if ( time() > $action_time + ( 30 * DAY_IN_SECONDS ) ) {
			return ++$count;
		}

		return $count;
	}

	/**
	 * Determines if the current admin page is the Analytics.
	 *
	 * @return bool True if the current menu item is the Analytics menu or one of it's sub menus.
	 */
	private function is_analytics_page(): bool {
		$current_page_slug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $current_page_slug !== 'wc-admin' ) {
			return false;
		}

		$current_page_path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$parts             = explode( '/', ltrim( $current_page_path, '/' ) );

		if ( isset( $parts[0] ) && $parts[0] === 'analytics' ) {
			return true;
		}

		return false;
	}
}
