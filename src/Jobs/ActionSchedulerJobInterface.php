<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ActionSchedulerJobInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface ActionSchedulerJobInterface extends JobInterface {

	/**
	 * Get the hook name for the "process item" action.
	 *
	 * This method is required by the job monitor.
	 *
	 * @return string
	 */
	public function get_process_item_hook(): string;

	/**
	 * Start the job.
	 *
	 * @param array $args
	 */
	public function start( array $args = [] );

}
