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
 * Class NotificationsSystemSlot
 *
 * Registers and enqueues the plugin-agnostic notification slot bundle on all
 * wc-admin pages, so the shared marketing-notifications store is always
 * available regardless of which wc-admin route the SPA session started on.
 *
 * The slot bundle is registered with a stable, shared handle so that other
 * plugins can declare it as a script dependency without depending on GLA.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class NotificationsSystemSlot implements Service, Registerable {

	use PluginHelper;

	/**
	 * @var AssetsHandlerInterface
	 */
	private $assets_handler;

	/**
	 * NotificationsSystemSlot constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 */
	public function __construct( AssetsHandlerInterface $assets_handler ) {
		$this->assets_handler = $assets_handler;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_enqueue_scripts',
			function () {
				if ( ! PageController::is_admin_page() ) {
					return;
				}

				$build_dir = "{$this->get_root_dir()}/js/build";

				$slot_script = new AdminScriptWithBuiltDependenciesAsset(
					'woocommerce-marketing-notifications-system-slot',
					'js/build/woo-marketing-notifications-slot',
					"{$build_dir}/woo-marketing-notifications-slot.asset.php",
					new BuiltScriptDependencyArray(
						[
							'dependencies' => [ 'wp-data', 'wp-element' ],
							'version'      => $this->get_version(),
						]
					)
				);

				$this->assets_handler->register( $slot_script );
				$this->assets_handler->enqueue( $slot_script );

				$slot_style = new AdminStyleAsset(
					'woocommerce-marketing-notifications-system-slot-css',
					'js/build/woo-marketing-notifications-slot',
					[],
					(string) filemtime( "{$build_dir}/woo-marketing-notifications-slot.css" )
				);

				$this->assets_handler->register( $slot_style );
				$this->assets_handler->enqueue( $slot_style );
			}
		);
	}
}
