<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\TaskList\TaskListTrait;

/**
 * Class CompleteSetup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\TaskList
 */
class CompleteSetup implements Service, Registerable {

	use TaskListTrait;

	/**
	 * Register a service.
	 */
	public function register(): void {
		/*
		 * Can't get this to work
		 *
		if ( ! $this->should_register_tasks() ) {
			return;
		}
		*/

		add_action(
			'admin_enqueue_scripts',
			function() {
				$this->register_task();
			}
		);

		register_deactivation_hook(
			__FILE__,
			function() {
				$this->remove_task();
			}
		);
	}

	/**
	 * Add task list item
	 */
	protected function register_task(): void {
		$script_path       = '/js/build/task-complete-setup.js';
		$script_asset_path = GLA_PATH . '/js/build/task-complete-setup.asset.php';

		$script_asset = file_exists( $script_asset_path )
		? require $script_asset_path
		: [
			'dependencies' => [],
			'version'      => filemtime( GLA_PATH . $script_path ),
		];

		$script_url = plugins_url( $script_path, GLA_FILE );

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
	 * Remove task list item on deactivation
	 */
	protected function remove_task(): void {
		// argument matches the task "key" property
		do_action(
			'remove_woocommerce_extended_task_list_item',
			'gla_complete_setup'
		);
	}
}
