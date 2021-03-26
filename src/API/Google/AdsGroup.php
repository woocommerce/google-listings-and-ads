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
 * Straight up copying is the sincerest form of flattery.
 * https://developers.google.com/google-ads/api/docs/samples/add-shopping-smart-ad
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
	 * AdsGroup constructor.
	 *
	 * @param GoogleAdsClient $client
	 * @param PositiveInteger $id
	 */
	public function __construct( GoogleAdsClient $client, PositiveInteger $id ) {
		$this->client = $client;
		$this->id     = $id;
	}

	/**
	 * Set up the additional objects for the specified campaign:
	 * Ad group
	 * Ad group ad
	 * Listing group
	 *
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 *
	 * @throws ApiException If any object isn't created.
	 */
	public function set_up_for_campaign( string $campaign_resource_name, string $campaign_name = '' ) {
		// Create Smart Shopping ad group.
		$created_ad_group_resource_name = $this->create_ad_group( $campaign_resource_name, $campaign_name );

		// Create Smart Shopping ad group ad.
		$this->create_ad_group_ad( $created_ad_group_resource_name );

		// Create ad group criterion containing listing group.
		$this->create_shopping_listing_group( $created_ad_group_resource_name );
	}

	/**
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 *
	 * @return string
	 * @throws ApiException If the ad group isn't created.
	 */
	protected function create_ad_group( string $campaign_resource_name, string $campaign_name = '' ): string {
		$ad_group = new AdGroup(
			[
				'name'     => $campaign_name . ' Ad Group',
				'campaign' => $campaign_resource_name,
				'type'     => AdGroupType::SHOPPING_SMART_ADS,
				'status'   => AdGroupStatus::ENABLED,
			]
		);

		// Creates an ad group operation.
		$operation = new AdGroupOperation();
		$operation->setCreate( $ad_group );
		$created_ad_group = $this->mutate_ad_group( $operation );

		return $created_ad_group->getResourceName();
	}

	/**
	 * @param AdGroupOperation $operation
	 *
	 * @return MutateAdGroupResult
	 * @throws ApiException If the mutate call fails.
	 */
	protected function mutate_ad_group( AdGroupOperation $operation ): MutateAdGroupResult {
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
	 * @throws ApiException If the ad group ad isn't created.
	 */
	protected function create_ad_group_ad( string $ad_group_resource_name ): string {
		$ad_group_ad = new AdGroupAd(
			[
				'ad'       => new Ad( [ 'shopping_smart_ad' => new ShoppingSmartAdInfo() ] ),
				'ad_group' => $ad_group_resource_name,
			]
		);

		// Creates an ad group ad operation.
		$operation = new AdGroupAdOperation();
		$operation->setCreate( $ad_group_ad );

		$created_ad_group_ad = $this->mutate_ad_group_ad( $operation );

		return $created_ad_group_ad->getResourceName();
	}

	/**
	 * @param AdGroupAdOperation $operation
	 *
	 * @return MutateAdGroupAdResult
	 * @throws ApiException If the mutate call fails.
	 */
	protected function mutate_ad_group_ad( AdGroupAdOperation $operation ): MutateAdGroupAdResult {
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
	 * @throws ApiException If the ad group criterion isn't created.
	 */
	protected function create_shopping_listing_group( string $ad_group_resource_name ): string {
		$ad_group_criterion = new AdGroupCriterion(
			[
				'ad_group'      => $ad_group_resource_name,
				'status'        => AdGroupAdStatus::ENABLED,
				'listing_group' => new ListingGroupInfo( [ 'type' => ListingGroupType::UNIT ] ),
			]
		);

		// Creates an ad group criterion operation.
		$operation = new AdGroupCriterionOperation();
		$operation->setCreate( $ad_group_criterion );
		$created_ad_group_ad = $this->mutate_shopping_listing_group( $operation );

		return $created_ad_group_ad->getResourceName();
	}

	/**
	 * @param AdGroupCriterionOperation $operation
	 *
	 * @return MutateAdGroupCriterionResult
	 * @throws ApiException If the mutate call fails.
	 */
	protected function mutate_shopping_listing_group( AdGroupCriterionOperation $operation ): MutateAdGroupCriterionResult {
		$response = $this->client->getAdGroupCriterionServiceClient()->mutateAdGroupCriteria(
			$this->get_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}
}
