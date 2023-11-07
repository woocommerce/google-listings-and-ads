<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Interface RecurringJobInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface RecurringJobInterface extends StartOnHookInterface {

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int
	 */
	public function get_interval(): int;
}
