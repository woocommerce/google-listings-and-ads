<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAssetGroupQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAssetGroupAssetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsListingGroupFilterQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;
use Google\Ads\GoogleAds\V11\Enums\AssetGroupStatusEnum\AssetGroupStatus;
use Google\Ads\GoogleAds\V11\Enums\ListingGroupFilterTypeEnum\ListingGroupFilterType;
use Google\Ads\GoogleAds\V11\Enums\ListingGroupFilterVerticalEnum\ListingGroupFilterVertical;
use Google\Ads\GoogleAds\V11\Resources\AssetGroup;
use Google\Ads\GoogleAds\V11\Resources\AssetGroupListingGroupFilter;
use Google\Ads\GoogleAds\V11\Resources\Asset;
use Google\Ads\GoogleAds\V11\Resources\AssetGroupAsset;
use Google\Ads\GoogleAds\V11\Services\AssetGroupListingGroupFilterOperation;
use Google\Ads\GoogleAds\V11\Services\AssetGroupOperation;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Services\MutateOperation;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V11\Enums\AssetFieldTypeEnum\AssetFieldType;


/**
 * Class AdsAssetGroup
 *
 * Used for the Performance Max Campaigns
 * https://developers.google.com/google-ads/api/docs/performance-max/asset-groups
 *
 * @since 1.12.2
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsAssetGroup implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected const TEMPORARY_ID = -3;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * List of asset group resource names.
	 *
	 * @var string[]
	 */
	protected $asset_groups;

	/**
	 * AdsAssetGroup constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Returns a set of operations to create an asset group.
	 *
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 * @return array
	 */
	public function create_operations( string $campaign_resource_name, string $campaign_name ): array {
		// Asset must be created before listing group.
		return [
			$this->asset_group_create_operation( $campaign_resource_name, $campaign_name ),
			$this->listing_group_create_operation(),
		];
	}

	/**
	 * Returns a set of operations to delete an asset group.
	 *
	 * @param string $campaign_resource_name
	 * @return array
	 */
	public function delete_operations( string $campaign_resource_name ): array {
		$asset_group_operations   = $this->asset_group_delete_operations( $campaign_resource_name );
		$listing_group_operations = $this->listing_group_delete_operations();

		// All assets must be deleted from the group before deleting the asset group.
		return array_merge(
			$listing_group_operations,
			$asset_group_operations
		);
	}

	/**
	 * Returns an asset group create operation.
	 *
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 *
	 * @return MutateOperation
	 */
	protected function asset_group_create_operation( string $campaign_resource_name, string $campaign_name ): MutateOperation {
		$asset_group = new AssetGroup(
			[
				'resource_name' => $this->temporary_resource_name(),
				'name'          => $campaign_name . ' Asset Group',
				'campaign'      => $campaign_resource_name,
				'status'        => AssetGroupStatus::ENABLED,
			]
		);

		$operation = ( new AssetGroupOperation() )->setCreate( $asset_group );
		return ( new MutateOperation() )->setAssetGroupOperation( $operation );
	}

	/**
	 * Returns an asset group listing group filter create operation.
	 *
	 * @return MutateOperation
	 */
	protected function listing_group_create_operation(): MutateOperation {
		$listing_group = new AssetGroupListingGroupFilter(
			[
				'asset_group' => $this->temporary_resource_name(),
				'type'        => ListingGroupFilterType::UNIT_INCLUDED,
				'vertical'    => ListingGroupFilterVertical::SHOPPING,
			]
		);

		$operation = ( new AssetGroupListingGroupFilterOperation() )->setCreate( $listing_group );
		return ( new MutateOperation() )->setAssetGroupListingGroupFilterOperation( $operation );
	}

	/**
	 * Returns an asset group delete operation.
	 *
	 * @param string $campaign_resource_name
	 *
	 * @return MutateOperation[]
	 */
	protected function asset_group_delete_operations( string $campaign_resource_name ): array {
		$operations         = [];
		$this->asset_groups = [];

		$results = ( new AdsAssetGroupQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'asset_group.campaign', $campaign_resource_name )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name        = $row->getAssetGroup()->getResourceName();
			$this->asset_groups[] = $resource_name;
			$operation            = ( new AssetGroupOperation() )->setRemove( $resource_name );
			$operations[]         = ( new MutateOperation() )->setAssetGroupOperation( $operation );
		}

		return $operations;
	}

	/**
	 * Returns an asset group listing group filter delete operation.
	 *
	 * @return MutateOperation[]
	 */
	protected function listing_group_delete_operations(): array {
		if ( empty( $this->asset_groups ) ) {
			return [];
		}

		$operations = [];
		$results    = ( new AdsListingGroupFilterQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'asset_group_listing_group_filter.asset_group', $this->asset_groups, 'IN' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name = $row->getAssetGroupListingGroupFilter()->getResourceName();
			$operation     = ( new AssetGroupListingGroupFilterOperation() )->setRemove( $resource_name );
			$operations[]  = ( new MutateOperation() )->setAssetGroupListingGroupFilterOperation( $operation );
		}

		return $operations;
	}

	/**
	 * Return a temporary resource name for the asset group.
	 *
	 * @return string
	 */
	protected function temporary_resource_name() {
		return ResourceNames::forAssetGroup( $this->options->get_ads_id(), self::TEMPORARY_ID );
	}

	/**
	 * Get Asset Groups for a specific campaign.
	 *
	 * @param int $campaign_id The campaign ID.
	 *
	 * @return array The asset groups for the campaign.
	 */
	protected function get_asset_groups_by_campaign_id( int $campaign_id ): array {
		$asset_groups = [];

		// TODO: LIMIT NUMBER OF RESULTS.
		$asset_group_results = ( new AdsAssetGroupQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'campaign.id', $campaign_id )
			->where( 'asset_group.status', 'REMOVED', '!=' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $asset_group_results->iterateAllElements() as $row ) {
			$resource_name  = $row->getAssetGroup()->getResourceName();
			$asset_groups[] = $resource_name;

		}

		return $asset_groups;
	}

	/**
	 * Get Asset Group Assets for a specific campaign.
	 *
	 * @param int $campaign_id The campaign ID.
	 *
	 * @return array The asset group assets for the campaign.
	 */
	public function get_asset_groups_assets( int $campaign_id ): array {
		$asset_groups = $this->get_asset_groups_by_campaign_id( $campaign_id );

		$asset_group_assets = [];
		$asset_results      = ( new AdsAssetGroupAssetQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'asset_group.resource_name', $asset_groups, 'IN' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $asset_results->iterateAllElements() as $row ) {

			$data = '';

			/** @var Asset $asset */
			$asset = $row->getAsset();

			/** @var AssetGroupAsset $asset_group_asset */
			$asset_group_asset = $row->getAssetGroupAsset();

			$field_type = strtolower( AssetFieldType::name( $asset_group_asset->getFieldType() ) );

			// TODO: HANDLE CONTENT IN A DIFFERENT FUNCTION.
			switch ( $asset->getType() ) {
				case AssetType::IMAGE:
					$data = $asset->getImageAsset()->getFullSize()->getUrl();
					break;
				case AssetType::TEXT:
					$data = $asset->getTextAsset()->getText();
					break;
			}

			$asset_data = [
				'id'      => $asset->getId(),
				'content' => $data,
			];

			$asset_group_assets[ $row->getAssetGroup()->getId() ]['assets'][ $field_type ][] = $asset_data;

		}

		return array_values( $asset_group_assets );
	}
}
