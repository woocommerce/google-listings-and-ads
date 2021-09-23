<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Something that can be run e.g. on a specific schedule.
 *
 * @since x.x.x
 */
interface Runnable {

	/**
	 * Run a task.
	 *
	 * @return void
	 */
	public function run(): void;

}
