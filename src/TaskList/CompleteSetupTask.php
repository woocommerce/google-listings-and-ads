<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\TaskList;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;

/**
 * Complete Setup Task to be added to the extended task list.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\TaskList
 */
class CompleteSetupTask extends Task implements Service, Registerable, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;

	/**
	 * Register a service.
	 *
	 * Add itself to the extended task list on init.
	 */
	public function register(): void {
		add_action(
			'init',
			function () {
				$list_id = 'extended';

				$this->task_list = TaskLists::get_list( $list_id );
				TaskLists::add_task( $list_id, $this );
			}
		);
	}
	/**
	 * Get the task id.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'gla_complete_setup';
	}

	/**
	 * Get the task name.
	 *
	 * @return string
	 */
	public function get_title() {
		return __(
			'Set up Google Listings & Ads',
			'google-listings-and-ads'
		);
	}

	/**
	 * Get the task description.
	 *
	 * @return string empty string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Get the task completion time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '20 minutes', 'google-listings-and-ads' );
	}

	/**
	 * Always dismissable.
	 *
	 * @return bool
	 */
	public function is_dismissable() {
		return true;
	}

	/**
	 * Get completion status.
	 * Forwards from the merchant center setup status.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->merchant_center->is_setup_complete();
	}

	/**
	 * Get the action URL.
	 *
	 * @return string Start page or dashboard is the setup is completed.
	 */
	public function get_action_url() {
		if ( ! $this->is_complete() ) {
			return admin_url( 'admin.php?page=wc-admin&path=/google/start' );
		}

		return admin_url( 'admin.php?page=wc-admin&path=/google/dashboard' );
	}
}
