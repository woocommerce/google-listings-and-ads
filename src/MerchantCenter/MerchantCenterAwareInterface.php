<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

defined( 'ABSPATH' ) || exit;

/**
 * Interface MerchantCenterAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
interface MerchantCenterAwareInterface {

	/**
	 * @param MerchantCenterService $merchant_center
	 */
	public function set_merchant_center_object( MerchantCenterService $merchant_center ): void;
}
