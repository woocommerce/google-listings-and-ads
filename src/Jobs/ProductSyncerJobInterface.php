<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ProductSyncerJobInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface ProductSyncerJobInterface extends MerchantCenterAwareInterface {

	/**
	 * Get whether Merchant Center setup is connected.
	 *
	 * @return bool
	 */
	public function is_mc_connected(): bool;

}
