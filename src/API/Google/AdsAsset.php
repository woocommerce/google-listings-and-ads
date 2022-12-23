<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V11\Enums\CallToActionTypeEnum\CallToActionType;
use Google\Ads\GoogleAds\V11\Resources\Asset;
use Google\Ads\GoogleAds\V11\Services\AssetOperation;
use Google\Ads\GoogleAds\V11\Services\MutateOperation;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;
use Google\Ads\GoogleAds\V11\Common\TextAsset;
use Google\Ads\GoogleAds\V11\Common\ImageAsset;
use Google\Ads\GoogleAds\V11\Common\CallToActionAsset;

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
class AdsAsset implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected const TEMPORARY_ID = -5;

	/**
	 * Return a temporary resource name for the asset.
	 *
	 * @param int $temporary_id The temporary ID to use for the asset.
	 * @return string
	 */
	protected function temporary_resource_name( $temporary_id = self::TEMPORARY_ID ) {
		return ResourceNames::forAsset( $this->options->get_ads_id(), $temporary_id );
	}

	/**
	 * Returns a set of operations to create multiple text assets.
	 *
	 * @param array $asset The assets to use the text asset.
	 * @param int   $temporary_id The temporary ID to use for the asset.
	 * @return MutateOperation The text asset mutation.
	 */
	public function create_operation_text_asset( array $asset, int $temporary_id = self::TEMPORARY_ID ): MutateOperation {
		return new MutateOperation(
			[
				'asset_operation' => new AssetOperation(
					[
						'create' => new Asset(
							[
								'resource_name' => $this->temporary_resource_name( $temporary_id ),
								'text_asset'    => new TextAsset( [ 'text' => $asset['content'] ] ),
							]
						),
					]
				),
			]
		);

	}

	/**
	 * Convert Asset data to an array.
	 *
	 * @param GoogleAdsRow $row Data row returned from a query request.
	 *
	 * @return array
	 */
	public function convert_asset( GoogleAdsRow $row ): array {
		/** @var Asset $asset */
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
			default:
				$data = '';
		}

		return [
			'id'      => $asset->getId(),
			'content' => $data,
		];
	}
}
