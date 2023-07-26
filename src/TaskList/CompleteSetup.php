<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\TaskList\CompleteSetupTask;

/**
 * Class CompleteSetup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\TaskList
 */
class CompleteSetup implements Service, Registerable {

	/**
	 * Register a service.
	 *
	 * Add CompleteSetupTask to the extended task list on init.
	 */
	public function register(): void {
		add_action(
			'init',
			function() {
				$task_list = 'extended';
				$task      = new CompleteSetupTask(
					TaskLists::get_list( $task_list )
				);
				TaskLists::add_task( $task_list, $task );

				do_action( 'add_woocommerce_extended_task_list_item', $task->get_id() );
			}
		);
	}
}
