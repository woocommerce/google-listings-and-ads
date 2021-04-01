<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsAwareness;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;
use Psr\Container\ContainerInterface;

/**
 * Class CompleteSetup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\TaskList
 */
class CompleteSetup implements Deactivateable, Service, Registerable, MerchantCenterAwareInterface {

	use AssetsAwareness;
	use MerchantCenterAwareTrait;
	use PluginHelper;
	use TaskListTrait;

	/**
	 * CompleteSetup constructor.
	 *
	 * @param ContainerInterface $container The container object.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->assets_handler = $container->get( AssetsHandlerInterface::class );
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->register_assets();

		add_action(
			'admin_enqueue_scripts',
			function() {
				if ( ! $this->should_register_tasks() ) {
					return;
				}

				$this->enqueue_assets();

				// argument matches the task "key" property
				do_action( 'add_woocommerce_extended_task_list_item', 'gla_complete_setup' );
			}
		);
	}

	/**
	 * Set up assets in the array of Assets.
	 */
	protected function setup_assets(): void {
		$this->assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'gla-task-complete-setup',
			'js/build/task-complete-setup',
			"{$this->get_root_dir()}/js/build/task-complete-setup.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$this->get_root_dir()}/js/build/task-complete-setup.js" ),
				]
			)
		) )->add_localization(
			'glaTaskData',
			[
				'isComplete' => $this->merchant_center->is_setup_complete(),
			]
		);
	}

	/**
	 * Deactivate the service.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// argument matches the task "key" property
		do_action( 'remove_woocommerce_extended_task_list_item', 'gla_complete_setup' );
	}
}
