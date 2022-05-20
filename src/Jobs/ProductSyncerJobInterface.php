<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ProductSyncerJobInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface ProductSyncerJobInterface {

	/**
	 * Get whether Merchant Center is connected and ready for syncing data.
	 *
	 * @since 1.13.0
	 * @return bool
	 */
	public function is_mc_ready_for_syncing(): bool;

}
