<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsAwareness;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use Psr\Container\ContainerInterface;

/**
 * Class CompleteSetup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\TaskList
 */
class CompleteSetup implements Deactivateable, Service, Registerable {

	use AssetsAwareness;
	use PluginHelper;
	use TaskListTrait;

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * CompleteSetup constructor.
	 *
	 * @param ContainerInterface $container The container object.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->assets_handler = $container->get( AssetsHandlerInterface::class );
		$this->options        = $container->get( OptionsInterface::class );
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->register_assets();
		$this->enqueue_assets();

		add_action(
			'admin_enqueue_scripts',
			function() {
				if ( ! $this->should_register_tasks() ) {
					return;
				}

				// argument matches the task "key" property
				do_action( 'add_woocommerce_extended_task_list_item', 'gla_complete_setup' );
			}
		);
	}

	/**
	 * Set up assets in the array of Assets.
	 */
	protected function setup_assets() {
		$this->assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'gla-task-complete-setup',
			'js/build/task-complete-setup',
			"{$this->get_root_dir()}/js/build/task-complete-setup.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => filemtime( "{$this->get_root_dir()}/js/build/task-complete-setup.js" ),
				]
			)
		) )->add_localization(
			'glaTaskData',
			[
				'isComplete' => $this->options->get( OptionsInterface::MC_SETUP_COMPLETE, false ),
			]
		);
	}

	/**
	 * Add task list item
	 */
	protected function register_task(): void {
		$script_path       = '/js/build/task-complete-setup.js';
		$script_asset_path = "{$this->get_root_dir()}/js/build/task-complete-setup.asset.php";

		$script_asset = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => filemtime( "{$this->get_root_dir()}/js/build/task-complete-setup.js" ),
			];

		$script_url = $this->get_plugin_url( $script_path );

		wp_register_script(
			'gla-task-complete-setup',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		$client_data = [
			'isComplete' => get_option( 'gla_mc_setup_complete', false ),
		];

		wp_localize_script( 'gla-task-complete-setup', 'glaTaskData', $client_data );
		wp_enqueue_script( 'gla-task-complete-setup' );

		// argument matches the task "key" property
		do_action(
			'add_woocommerce_extended_task_list_item',
			'gla_complete_setup'
		);
	}

	/**
	 * Deactivate the service.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// argument matches the task "key" property
		do_action(
			'remove_woocommerce_extended_task_list_item',
			'gla_complete_setup'
		);
	}
}
