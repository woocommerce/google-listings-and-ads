<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox\MetaBoxInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsAwareness;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\ViewFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use Automattic\WooCommerce\GoogleListingsAndAds\View\ViewException;

/**
 * Class Admin
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Pages
 */
class Admin implements Service, Registerable, Conditional {

	use AssetsAwareness, AdminConditional, PluginHelper;

	/**
	 * @var ViewFactory
	 */
	protected $view_factory;

	/**
	 * Admin constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 * @param ViewFactory            $view_factory
	 */
	public function __construct( AssetsHandlerInterface $assets_handler, ViewFactory $view_factory ) {
		$this->assets_handler = $assets_handler;
		$this->view_factory   = $view_factory;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->register_assets();

		add_action(
			'admin_enqueue_scripts',
			function() {
				if ( wc_admin_is_registered_page() ) {
					$this->enqueue_assets();
				}
			}
		);
	}

	/**
	 * Set up the array of assets.
	 */
	protected function setup_assets(): void {
		$this->assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'google-listings-and-ads',
			'js/build/index',
			"{$this->get_root_dir()}/js/build/index.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => filemtime( "{$this->get_root_dir()}/js/build/index.js" ),
				]
			)
		) )->add_localization(
			'glaData',
			[
				'placeholder' => 'placeholder',
			]
		);

		$this->assets[] = ( new AdminStyleAsset(
			'google-listings-and-ads-css',
			'/js/build/index',
			defined( 'WC_ADMIN_PLUGIN_FILE' ) ? [ 'wc-admin-app' ] : [],
			(string) filemtime( "{$this->get_root_dir()}/js/build/index.css" )
		) );
	}

	/**
	 * Adds a meta box.
	 *
	 * @param MetaBoxInterface $meta_box
	 */
	public function add_meta_box( MetaBoxInterface $meta_box ) {
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
	 * @param string $view
	 * @param array  $context_variables
	 *
	 * @return string
	 *
	 * @throws ViewException If the view doesn't exist or can't be loaded.
	 */
	public function get_view( string $view, array $context_variables = [] ): string {
		return $this->view_factory->create( self::get_view_path( $view ) )
							->render( $context_variables );
	}

	/**
	 * @param string $view
	 *
	 * @return string
	 */
	protected static function get_view_path( string $view ): string {
		return path_join( 'src/Admin/views', $view );
	}
}
