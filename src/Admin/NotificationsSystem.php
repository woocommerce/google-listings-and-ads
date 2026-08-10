<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationsSystem
 *
 * Enqueues the notifications-system JS bundle and its paired CSS on all
 * wc-admin pages. The bundle itself decides, client-side, whether the
 * current SPA route is the Marketing overview page before fetching or
 * rendering any notifications.
 *
 * The main `google-listings-and-ads` bundle isn't guaranteed to be present on
 * every page this bundle loads on (e.g. the core WooCommerce Marketing
 * overview page), so this bundle provides its own fallback `glaData`. It's
 * injected as `window.glaData = window.glaData || {...}` (a merge, not an
 * unconditional `var glaData = {...}` assignment) so that on pages where the
 * main bundle's richer `glaData` is already present, this doesn't clobber it.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class NotificationsSystem implements Service, Registerable {

	use PluginHelper;

	/**
	 * @var AssetsHandlerInterface
	 */
	private $assets_handler;

	/**
	 * @var OptionsInterface
	 */
	private $options;

	/**
	 * NotificationsSystem constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param OptionsInterface       $options
	 */
	public function __construct(
		AssetsHandlerInterface $assets_handler,
		OptionsInterface $options
	) {
		$this->assets_handler = $assets_handler;
		$this->options        = $options;
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

				$script = new AdminScriptWithBuiltDependenciesAsset(
					'google-listings-and-ads-notifications-system',
					'js/build/notifications-system',
					"{$build_dir}/notifications-system.asset.php",
					new BuiltScriptDependencyArray(
						[
							'dependencies' => [ 'woocommerce-marketing-notifications-system-slot' ],
							'version'      => $this->get_version(),
						]
					)
				);

				$style = new AdminStyleAsset(
					'google-listings-and-ads-notifications-system-css',
					'js/build/notifications-system',
					[],
					(string) filemtime( "{$build_dir}/notifications-system.css" )
				);

				$this->assets_handler->register_many( [ $script, $style ] );
				$this->assets_handler->enqueue_many( [ $script, $style ] );

				wp_add_inline_script(
					$script->get_handle(),
					'window.glaData = window.glaData || ' . wp_json_encode( $this->get_gla_data() ) . ';',
					'before'
				);
			}
		);
	}

	/**
	 * Get the fallback glaData required by the notifications-system bundle when
	 * the main bundle's own glaData isn't present on the current page.
	 *
	 * @return array
	 */
	private function get_gla_data(): array {
		return [
			'slug'          => $this->get_slug(),
			'dateFormat'    => get_option( 'date_format' ),
			'initialWpData' => [
				'version' => $this->get_version(),
				'mcId'    => $this->options->get_merchant_id() ?: null,
				'adsId'   => $this->options->get_ads_id() ?: null,
			],
		];
	}
}
