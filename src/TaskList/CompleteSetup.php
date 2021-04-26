<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\Asset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Deactivateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;

/**
 * Class CompleteSetup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\TaskList
 */
class CompleteSetup implements Deactivateable, Service, Registerable, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use PluginHelper;
	use TaskListTrait;

	/**
	 * @var AssetsHandlerInterface
	 */
	protected $assets_handler;

	/**
	 * CompleteSetup constructor.
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
		$this->assets_handler->add_many( $this->get_assets() );

		add_action(
			'admin_enqueue_scripts',
			function () {
				if ( ! $this->should_register_tasks() ) {
					return;
				}

				$this->assets_handler->enqueue_many( $this->get_assets() );

				// argument matches the task "key" property
				do_action( 'add_woocommerce_extended_task_list_item', 'gla_complete_setup' );
			}
		);
	}

	/**
	 * Return an array of assets.
	 *
	 * @return Asset[]
	 */
	protected function get_assets(): array {
		$assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'gla-task-complete-setup',
			'js/build/task-complete-setup',
			"{$this->get_root_dir()}/js/build/task-complete-setup.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => (string) filemtime( "{$this->get_root_dir()}/js/build/task-complete-setup.js" ),
				]
			),
			function () {
				return $this->should_register_tasks();
			}
		) )->add_localization(
			'glaTaskData',
			[
				'isComplete' => $this->merchant_center->is_setup_complete(),
			]
		);

		return $assets;
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
