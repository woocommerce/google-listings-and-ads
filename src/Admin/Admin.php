<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\ViewFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use Automattic\WooCommerce\GoogleListingsAndAds\View\ViewException;

/**
 * Class Admin
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Pages
 */
class Admin implements Service, Registerable, Conditional {

	use AdminConditional;
	use PluginHelper;

	/**
	 * @var AssetsHandlerInterface
	 */
	protected $assets_handler;

	/**
	 * @var ViewFactory
	 */
	protected $view_factory;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @var AdsService
	 */
	protected $ads;

	/**
	 * Admin constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param ViewFactory            $view_factory
	 * @param MerchantCenterService  $merchant_center
	 * @param AdsService             $ads
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, ViewFactory $view_factory, MerchantCenterService $merchant_center, AdsService $ads ) {
		$this->assets_handler  = $assets_handler;
		$this->view_factory    = $view_factory;
		$this->merchant_center = $merchant_center;
		$this->ads             = $ads;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->assets_handler->add_many( $this->get_assets() );

		add_action(
			'admin_enqueue_scripts',
			function() {
				$this->assets_handler->enqueue_many( $this->get_assets() );
			}
		);

		add_action(
			"plugin_action_links_{$this->get_plugin_basename()}",
			function( $links ) {
				return $this->add_plugin_links( $links );
			}
		);
	}

	/**
	 * Return an array of assets.
	 *
	 * @return Asset[]
	 */
	protected function get_assets(): array {
		$wc_admin_condition = function() {
			return wc_admin_is_registered_page();
		};

		$assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'google-listings-and-ads',
			'js/build/index',
			"{$this->get_root_dir()}/js/build/index.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$this->get_root_dir()}/js/build/index.js" ),
				]
			),
			$wc_admin_condition
		) )->add_inline_script(
			'glaData',
			[
				'mcSetupComplete'     => $this->merchant_center->is_setup_complete(),
				'mcSupportedCountry'  => $this->merchant_center->is_country_supported(),
				'mcSupportedLanguage' => $this->merchant_center->is_language_supported(),
				'adsSetupComplete'    => $this->ads->is_setup_complete(),
				'enableReports'       => $this->enableReports(),
			]
		);

		$assets[] = ( new AdminStyleAsset(
			'google-listings-and-ads-css',
			'/js/build/index',
			defined( 'WC_ADMIN_PLUGIN_FILE' ) ? [ 'wc-admin-app' ] : [],
			(string) filemtime( "{$this->get_root_dir()}/js/build/index.css" ),
			$wc_admin_condition
		) );

		$product_condition = function () {
			$screen = get_current_screen();
			return ( null !== $screen && 'product' === $screen->id );
		};

		$assets[] = ( new AdminScriptAsset(
			'gla-custom-inputs',
			'js/build/custom-inputs',
			[],
			'',
			$product_condition
		) );
		$assets[] = ( new AdminStyleAsset(
			'gla-product-attributes-css',
			'js/build/product-attributes',
			[],
			'',
			$product_condition
		) );

		return $assets;
	}

	/**
	 * Adds links to the plugin's row in the "Plugins" wp-admin page.
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
	 * @param array $links The existing list of links that will be rendered.
	 */
	protected function add_plugin_links( $links ): array {
		$plugin_links = [];

		// Display settings url if setup is complete otherwise link to get started page
		if ( $this->merchant_center->is_setup_complete() ) {
			$plugin_links[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_attr( $this->get_settings_url() ),
				esc_html__( 'Settings', 'google-listings-and-ads' )
			);
		} else {
			$plugin_links[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_attr( $this->get_start_url() ),
				esc_html__( 'Get Started', 'google-listings-and-ads' )
			);
		}

		$plugin_links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_attr( $this->get_documentation_url() ),
			esc_html__( 'Documentation', 'google-listings-and-ads' )
		);

		// Add new links to the beginning
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Adds a meta box.
	 *
	 * @param MetaBoxInterface $meta_box
	 */
	public function add_meta_box( MetaBoxInterface $meta_box ) {
		add_filter(
			"postbox_classes_{$meta_box->get_screen()}_{$meta_box->get_id()}",
			function ( array $classes ) use ( $meta_box ) {
				return array_merge( $classes, $meta_box->get_classes() );
			}
		);

		add_meta_box(
			$meta_box->get_id(),
			$meta_box->get_title(),
			$meta_box->get_callback(),
			$meta_box->get_screen(),
			$meta_box->get_context(),
			$meta_box->get_priority(),
			$meta_box->get_callback_args()
		);
	}

	/**
	 * @param string $view              Name of the view
	 * @param array  $context_variables Array of variables to pass to the view
	 *
	 * @return string The rendered view
	 *
	 * @throws ViewException If the view doesn't exist or can't be loaded.
	 */
	public function get_view( string $view, array $context_variables = [] ): string {
		return $this->view_factory->create( $view )
							->render( $context_variables );
	}

	/**
	 * Only show reports if we enable it through a snippet.
	 *
	 * @return bool Whether reports should be enabled .
	 */
	protected function enableReports(): bool {
		return apply_filters( 'gla_enable_reports', true );
	}
}
