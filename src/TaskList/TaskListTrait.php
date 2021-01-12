<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\Loader;

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
		if (
			! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) ||
			! Loader::is_admin_page() ||
			! Onboarding::should_show_tasks()
		) {
			return false;
		}
		return true;
	}
}
