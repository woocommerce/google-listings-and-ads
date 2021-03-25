<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google\Ads\GoogleAds\V6\Common\ListingGroupInfo;
use Google\Ads\GoogleAds\V6\Common\ShoppingSmartAdInfo;
use Google\Ads\GoogleAds\V6\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V6\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\Ads\GoogleAds\V6\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V6\Enums\ListingGroupTypeEnum\ListingGroupType;
use Google\Ads\GoogleAds\V6\Resources\Ad;
use Google\Ads\GoogleAds\V6\Resources\AdGroup;
use Google\Ads\GoogleAds\V6\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V6\Resources\AdGroupCriterion;
use Google\Ads\GoogleAds\V6\Services\AdGroupAdOperation;
use Google\Ads\GoogleAds\V6\Services\AdGroupCriterionOperation;
use Google\Ads\GoogleAds\V6\Services\AdGroupOperation;
use Google\Ads\GoogleAds\V6\Services\MutateAdGroupAdResult;
use Google\Ads\GoogleAds\V6\Services\MutateAdGroupCriterionResult;
use Google\Ads\GoogleAds\V6\Services\MutateAdGroupResult;
use Google\ApiCore\ApiException;

/**
 * Class AdsGroup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsGroup {

	use AdsIdTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * Ads constructor.
	 *
	 * @param GoogleAdsClient $client
	 * @param PositiveInteger $id
	 */
	public function __construct( GoogleAdsClient $client, PositiveInteger $id ) {
		$this->client = $client;
		$this->id     = $id;
	}

	/**
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 *
	 * @return string
	 * @throws ApiException if the ad group isn't created
	 */
	public function create_ad_group( string $campaign_resource_name, string $campaign_name = '' ): string {
		$adGroup = new AdGroup(
			[
				'name'     => $campaign_name . ' Ad Group',
				'campaign' => $campaign_resource_name,
				'type'     => AdGroupType::SHOPPING_SMART_ADS,
				'status'   => AdGroupStatus::ENABLED,
			]
		);

		// Creates an ad group operation.
		$operation = new AdGroupOperation();
		$operation->setCreate( $adGroup );
		$created_ad_group = $this->mutate_ad_group( $operation );

		return $created_ad_group->getResourceName();
	}

	/**
	 * @param AdGroupOperation $operation
	 *
	 * @return MutateAdGroupResult
	 * @throws ApiException if the mutate call fails
	 */
	public function mutate_ad_group( AdGroupOperation $operation ): MutateAdGroupResult {
		$response = $this->client->getAdGroupServiceClient()->mutateAdGroups(
			$this->get_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}

	/**
	 * @param string $ad_group_resource_name
	 *
	 * @return string
	 * @throws ApiException if the ad group ad isn't created
	 */
	public function create_ad_group_ad( string $ad_group_resource_name ): string {
		$adGroupAd = new AdGroupAd(
			[
				'ad'       => new Ad( [ 'shopping_smart_ad' => new ShoppingSmartAdInfo() ] ),
				'ad_group' => $ad_group_resource_name,
			]
		);

		// Creates an ad group ad operation.
		$operation = new AdGroupAdOperation();
		$operation->setCreate( $adGroupAd );

		$created_ad_group_ad = $this->mutate_ad_group_ad( $operation );

		return $created_ad_group_ad->getResourceName();
	}

	/**
	 * @param AdGroupAdOperation $operation
	 *
	 * @return MutateAdGroupAdResult
	 * @throws ApiException if the mutate call fails
	 */
	public function mutate_ad_group_ad( AdGroupAdOperation $operation ): MutateAdGroupAdResult {
		$response = $this->client->getAdGroupAdServiceClient()->mutateAdGroupAds(
			$this->get_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}

	/**
	 * @param string $ad_group_resource_name
	 *
	 * @return string
	 * @throws ApiException if the ad group criterion isn't created
	 */
	public function create_shopping_listing_group( string $ad_group_resource_name ): string {
		// Creates a new ad group criterion. This will contain a listing group.
		// This will be the listing group for 'All products' and will contain a single root node.
		$adGroupCriterion = new AdGroupCriterion(
			[
				'ad_group'      => $ad_group_resource_name,
				'status'        => AdGroupAdStatus::ENABLED,
				// Creates a new listing group. This will be the top-level "root" node.
				// Sets the type of the listing group to be a biddable unit.
				'listing_group' => new ListingGroupInfo( [ 'type' => ListingGroupType::UNIT ] ),
			// Note: Listing groups do not require bids for Smart Shopping campaigns.
			]
		);

		// Creates an ad group criterion operation.
		$operation = new AdGroupCriterionOperation();
		$operation->setCreate( $adGroupCriterion );
		$created_ad_group_ad = $this->mutate_shopping_listing_group( $operation );

		return $created_ad_group_ad->getResourceName();
	}

	/**
	 * @param AdGroupCriterionOperation $operation
	 *
	 * @return MutateAdGroupCriterionResult
	 * @throws ApiException if the mutate call fails
	 */
	public function mutate_shopping_listing_group( AdGroupCriterionOperation $operation ): MutateAdGroupCriterionResult {
		$response = $this->client->getAdGroupCriterionServiceClient()->mutateAdGroupCriteria(
			$this->get_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}
}
