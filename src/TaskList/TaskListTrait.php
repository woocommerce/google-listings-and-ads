<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\PageController;

/**
 * Trait TaskListTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\TaskList
 */
trait TaskListTrait {

	/**
	 * Determine whether tasks should be registered
	 *
	 * @return bool
	 */
	protected function should_register_tasks(): bool {
		if ( method_exists( PageController::class, 'is_admin_page' ) ) {
			return PageController::is_admin_page() && $this->check_should_show_tasks();
		}

		return class_exists( Loader::class ) && Loader::is_admin_page() && $this->check_should_show_tasks();
	}

	/**
	 * Helper function to check if UI should show tasks.
	 *
	 * @return bool
	 */
	private function check_should_show_tasks(): bool {
		if ( version_compare( WC_VERSION, '5.9', '<' ) ) {
			return Onboarding::should_show_tasks();
		}

		$setup_list    = TaskLists::get_list( 'setup' );
		$extended_list = TaskLists::get_list( 'extended' );

		return ( $setup_list && ! $setup_list->is_hidden() ) || ( $extended_list && ! $extended_list->is_hidden() );
	}
}
