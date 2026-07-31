<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;

/**
 * Class NotificationManager
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 *
 * Manages the display of a single, aggregated notification pill in the admin menu.
 * It relies on a filter to gather the total count, currently contributed to solely
 * by the NotificationService, though the filter remains open for other contributors.
 */
class NotificationManager implements ContainerAwareInterface, Service, Registerable {

	use PluginHelper;
	use ContainerAwareTrait;

	/**
	 * @var AssetsHandlerInterface
	 */
	protected $assets_handler;

	/**
	 * @var NotificationService
	 */
	protected $notification_service;

	/**
	 * NotificationManager constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param NotificationService    $notification_service
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, NotificationService $notification_service ) {
		$this->assets_handler       = $assets_handler;
		$this->notification_service = $notification_service;
	}

	/**
	 * Register the service, hooking into admin_menu to display notifications.
	 */
	public function register(): void {
		// Hook into admin_menu with a high priority (e.g., 20) to ensure
		// all other menu items have been registered by WooCommerce and other plugins.
		add_action( 'admin_menu', [ $this, 'display_aggregated_notification_pill' ], 20 );

		add_filter( 'google_for_woocommerce_admin_menu_notification_count', [ $this, 'notifications_count' ] );
	}

	/**
	 * Register assets.
	 *
	 * @return void
	 */
	protected function register_assets(): void {
		$notification_manager = new AdminScriptWithBuiltDependenciesAsset(
			'notification-manager',
			'js/build/notification-manager',
			"{$this->get_root_dir()}/js/build/notification-manager.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [ 'wp-hooks' ],
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
	protected function is_marketing_page(): bool {
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
		// The badge must never be shown to users who cannot manage WooCommerce.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		global $menu, $submenu;

		// Initialize the count and apply the filter to get the total aggregated count.
		// All parts of the plugin (and other plugins) that need to add to the notification
		// should hook into this filter.
		$total_notification_count = apply_filters( 'google_for_woocommerce_admin_menu_notification_count', 0 );

		// Only proceed if there's at least one notification.
		if ( $total_notification_count > 0 ) {
			// Register assets.
			$this->register_assets();

			$badge_html = ' <span class="update-plugins count-' . $total_notification_count . '"><span class="update-count">' . $total_notification_count . '</span></span>';

			// Determine if the current page being loaded is within the Marketing section.
			$is_on_marketing_child_page = $this->is_marketing_page();

			if ( $is_on_marketing_child_page ) {
				// If on a Marketing child page, add the pill to the 'Overview' sub-menu item.
				// This means the user has the Marketing menu expanded and is viewing one of its sub-pages.
				$marketing_parent_slug   = Dashboard::MARKETING_MENU_SLUG; // Use constant for parent slug
				$marketing_overview_path = Dashboard::MARKETING_OVERVIEW_PATH; // Use constant for Overview path

				if ( isset( $submenu[ $marketing_parent_slug ] ) ) {
					foreach ( $submenu[ $marketing_parent_slug ] as $key => $submenu_item ) {
						// Use the submenu's slug (index 2) for robustness against translations.
						// The slug will contain the path defined by the plugin.
						if ( isset( $submenu_item[2] ) && strpos( $submenu_item[2], $marketing_overview_path ) !== false ) {
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
	 * Adds the number of active notifications from the NotificationService to the count.
	 *
	 * @param int $count The initial count.
	 * @return int The updated notification count including the active notifications.
	 */
	public function notifications_count( int $count ): int {
		return $count + count( $this->notification_service->get_notifications() );
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
