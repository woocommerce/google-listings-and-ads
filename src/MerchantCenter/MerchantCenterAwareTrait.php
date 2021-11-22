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
	 * The MerchantStatuses object.
	 *
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * @param MerchantCenterService $merchant_center
	 */
	public function set_merchant_center_object( MerchantCenterService $merchant_center ): void {
		$this->merchant_center = $merchant_center;
	}

	/**
	 * @param MerchantStatuses $merchant_statuses
	 */
	public function set_merchant_statuses_object( MerchantStatuses $merchant_statuses ): void {
		$this->merchant_statuses = $merchant_statuses;
	}
}
