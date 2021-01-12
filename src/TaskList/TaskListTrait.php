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
		return class_exists( Loader::class ) && Loader::is_admin_page() && Onboarding::should_show_tasks();
	}
}
