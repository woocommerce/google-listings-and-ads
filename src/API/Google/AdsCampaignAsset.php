<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\V22\ResourceNames;
use Google\Ads\GoogleAds\V22\Resources\CampaignAsset;
use Google\Ads\GoogleAds\V22\Services\CampaignAssetOperation;
use Google\Ads\GoogleAds\V22\Services\MutateOperation;
use Google\Ads\GoogleAds\V22\Enums\AssetFieldTypeEnum\AssetFieldType as AssetFieldTypeEnum;

/**
 * Class AdsCampaignAsset
 *
 * Handles linking assets to campaigns at the campaign level.
 * Required for Performance Max campaigns with Brand Guidelines enabled.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaignAsset implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * AdsCampaignAsset constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Create operations to link business name and logo assets to a campaign.
	 *
	 * @param int   $campaign_id       The campaign ID.
	 * @param array $business_name_ids Array of business name asset IDs to link.
	 * @param array $logo_ids          Array of logo asset IDs to link.
	 *
	 * @return MutateOperation[]
	 */
	public function create_link_operations( int $campaign_id, array $business_name_ids = [], array $logo_ids = [] ): array {
		$operations        = [];
		$campaign_resource = ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id );

		// Link business name assets.
		foreach ( $business_name_ids as $asset_id ) {
			$operations[] = $this->create_campaign_asset_operation(
				$campaign_resource,
				$asset_id,
				AssetFieldTypeEnum::BUSINESS_NAME
			);
		}

		// Link logo assets (LOGO field type for Brand Guidelines campaign assets).
		foreach ( $logo_ids as $asset_id ) {
			$operations[] = $this->create_campaign_asset_operation(
				$campaign_resource,
				$asset_id,
				AssetFieldTypeEnum::LOGO
			);
		}

		return $operations;
	}

	/**
	 * Create a campaign asset link operation.
	 *
	 * @param string $campaign_resource Campaign resource name.
	 * @param int    $asset_id          Asset ID.
	 * @param int    $field_type        Asset field type enum value.
	 *
	 * @return MutateOperation
	 */
	protected function create_campaign_asset_operation( string $campaign_resource, int $asset_id, int $field_type ): MutateOperation {
		$asset_resource = ResourceNames::forAsset( $this->options->get_ads_id(), $asset_id );

		$campaign_asset = new CampaignAsset(
			[
				'campaign'   => $campaign_resource,
				'asset'      => $asset_resource,
				'field_type' => $field_type,
			]
		);

		$operation = ( new CampaignAssetOperation() )->setCreate( $campaign_asset );
		return ( new MutateOperation() )->setCampaignAssetOperation( $operation );
	}

	/**
	 * Create a campaign asset removal operation.
	 *
	 * @param string $campaign_asset_resource Campaign asset resource name.
	 *
	 * @return MutateOperation
	 */
	public function create_remove_operation( string $campaign_asset_resource ): MutateOperation {
		$operation = ( new CampaignAssetOperation() )->setRemove( $campaign_asset_resource );
		return ( new MutateOperation() )->setCampaignAssetOperation( $operation );
	}
}
