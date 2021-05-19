<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

defined( 'ABSPATH' ) || exit;

/**
 * Trait AdsAwareTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
trait AdsAwareTrait {

	/**
	 * The AdsService object.
	 *
	 * @var AdsService
	 */
	protected $ads_service;

	/**
	 * @param AdsService $ads_service
	 */
	public function set_ads_object( AdsService $ads_service ): void {
		$this->ads_service = $ads_service;
	}
}
