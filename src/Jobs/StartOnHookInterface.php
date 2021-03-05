<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Interface StartOnHookInterface
 *
 * Action Scheduler jobs that implement this interface will start on a specific action hook.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface StartOnHookInterface extends ActionSchedulerJobInterface {

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return string
	 */
	public function get_start_hook(): string;

}
