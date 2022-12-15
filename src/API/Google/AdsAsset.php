<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V11\Enums\CallToActionTypeEnum\CallToActionType;


/**
 * Class AdsAsset
 *
 * Used for the Performance Max Campaigns
 * https://developers.google.com/google-ads/api/docs/performance-max/assets
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsAsset {

	/**
	 * Convert Asset data to an array.
	 *
	 * @param GoogleAdsRow $row Data row returned from a query request.
	 *
	 * @return array
	 */
	public function convert_asset( GoogleAdsRow $row ): array {
		$asset = $row->getAsset();

		switch ( $asset->getType() ) {
			case AssetType::IMAGE:
				$data = $asset->getImageAsset()->getFullSize()->getUrl();
				break;
			case AssetType::TEXT:
				$data = $asset->getTextAsset()->getText();
				break;
			case AssetType::CALL_TO_ACTION:
				$data = CallToActionType::name( $asset->getCallToActionAsset()->getCallToAction() );
				break;
		}

		return [
			'id'      => $asset->getId(),
			'content' => $data,
		];
	}
}
