<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationSystem
 *
 * Enqueues the notification-system JS bundle on the WooCommerce Marketing overview page.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class NotificationSystem implements Service, Registerable {

	use PluginHelper;

	/**
	 * @var AssetsHandlerInterface
	 */
	protected AssetsHandlerInterface $assets_handler;

	/**
	 * NotificationSystem constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 */
	public function __construct( AssetsHandlerInterface $assets_handler ) {
		$this->assets_handler = $assets_handler;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		$asset = new AdminScriptWithBuiltDependenciesAsset(
			'gla-notification-system',
			'js/build/notification-system',
			"{$this->get_root_dir()}/js/build/notification-system.asset.php",
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

		$style = new AdminStyleAsset(
			'gla-notification-system-css',
			'/js/build/notification-system',
			[],
			(string) filemtime( "{$this->get_root_dir()}/js/build/notification-system.css" ),
			function () {
				return $this->is_marketing_overview_page();
			}
		);

		$this->assets_handler->register( $asset );
		$this->assets_handler->register( $style );

		add_action(
			'admin_enqueue_scripts',
			function () use ( $asset, $style ) {
				if ( ! $this->is_marketing_overview_page() ) {
					return;
				}
				$this->assets_handler->enqueue( $asset );
				$this->assets_handler->enqueue( $style );
			}
		);
	}

	/**
	 * Checks if the current page is the WooCommerce Marketing overview page.
	 *
	 * @return bool
	 */
	private function is_marketing_overview_page(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		return 'wc-admin' === $page && '/marketing' === $path;
	}
}
