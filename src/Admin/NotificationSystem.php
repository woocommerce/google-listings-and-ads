<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationSystem
 *
 * Enqueues the notifications-system JS bundle and its paired CSS on the
 * WooCommerce Marketing overview page (page=wc-admin&path=/marketing).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class NotificationSystem implements Service, Registerable {

	use PluginHelper;

	/**
	 * @var AssetsHandlerInterface
	 */
	private $assets_handler;

	/**
	 * @var MerchantCenterService
	 */
	private $merchant_center;

	/**
	 * @var OptionsInterface
	 */
	private $options;

	/**
	 * NotificationSystem constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param MerchantCenterService  $merchant_center
	 * @param OptionsInterface       $options
	 */
	public function __construct(
		AssetsHandlerInterface $assets_handler,
		MerchantCenterService $merchant_center,
		OptionsInterface $options
	) {
		$this->assets_handler  = $assets_handler;
		$this->merchant_center = $merchant_center;
		$this->options         = $options;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_enqueue_scripts',
			function () {
				if ( ! $this->is_marketing_overview_page() ) {
					return;
				}

				$build_dir = "{$this->get_root_dir()}/js/build";

				$script = ( new AdminScriptWithBuiltDependenciesAsset(
					'google-listings-and-ads-notifications-system',
					'js/build/notifications-system',
					"{$build_dir}/notifications-system.asset.php",
					new BuiltScriptDependencyArray(
						[
							'dependencies' => [ 'woocommerce-marketing-notifications-system-slot' ],
							'version'      => $this->get_version(),
						]
					)
				) )->add_inline_script( 'glaData', $this->get_gla_data() );

				$style = new AdminStyleAsset(
					'google-listings-and-ads-notifications-system-css',
					'js/build/notifications-system',
					[],
					(string) filemtime( "{$build_dir}/notifications-system.css" )
				);

				$this->assets_handler->register_many( [ $script, $style ] );
				$this->assets_handler->enqueue_many( [ $script, $style ] );
			}
		);
	}

	/**
	 * Get the inline glaData required by the notifications-system bundle.
	 *
	 * @return array
	 */
	private function get_gla_data(): array {
		return [
			'dateFormat'      => get_option( 'date_format' ),
			'mcSetupComplete' => $this->merchant_center->is_setup_complete(),
			'initialWpData'   => [
				'version' => $this->get_version(),
				'mcId'    => $this->options->get_merchant_id() ?: null,
				'adsId'   => $this->options->get_ads_id() ?: null,
			],
		];
	}

	/**
	 * Determine if the current admin page is the WooCommerce Marketing overview page.
	 *
	 * @return bool
	 */
	private function is_marketing_overview_page(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		return 'wc-admin' === $page && '/marketing' === $path;
	}
}
