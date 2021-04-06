<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MerchantCenterAwareTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
trait MerchantCenterAwareTrait {

	/**
	 * The MerchantCenterService object.
	 *
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * @param MerchantCenterService $merchant_center
	 */
	public function set_merchant_center_object( MerchantCenterService $merchant_center ): void {
		$this->merchant_center = $merchant_center;
	}
}
