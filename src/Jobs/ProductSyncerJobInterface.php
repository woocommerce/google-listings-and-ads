<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ProductSyncerJobInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
interface ProductSyncerJobInterface extends OptionsAwareInterface {

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	public function is_mc_setup(): bool;

}
