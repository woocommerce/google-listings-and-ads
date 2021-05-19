<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

defined( 'ABSPATH' ) || exit;

/**
 * Interface AdsAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
interface AdsAwareInterface {

	/**
	 * @param AdsService $ads_service
	 */
	public function set_ads_object( AdsService $ads_service ): void;
}
