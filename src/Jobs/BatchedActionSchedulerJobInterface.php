<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Interface BatchedActionSchedulerJobInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface BatchedActionSchedulerJobInterface extends ActionSchedulerJobInterface {

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/create_batch
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args Action args.
	 *
	 * @throws Exception If an error occurs.
	 */
	public function handle_create_batch_action( int $batch_number, array $args = [] );

	/**
	 * Handles processing a single batch action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/process_item
	 *
	 * @param array $items The job items from the current batch.
	 * @param array $args Action args.
	 *
	 * @throws Exception If an error occurs.
	 */
	public function handle_process_items_action( array $items, array $args = [] );
}
