<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsGroupQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsGroupAdQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsListingGroupQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\V9\Common\ListingGroupInfo;
use Google\Ads\GoogleAds\V9\Common\ShoppingSmartAdInfo;
use Google\Ads\GoogleAds\V9\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V9\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\Ads\GoogleAds\V9\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V9\Enums\ListingGroupTypeEnum\ListingGroupType;
use Google\Ads\GoogleAds\V9\Resources\Ad;
use Google\Ads\GoogleAds\V9\Resources\AdGroup;
use Google\Ads\GoogleAds\V9\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V9\Resources\AdGroupCriterion;
use Google\Ads\GoogleAds\V9\Services\AdGroupAdOperation;
use Google\Ads\GoogleAds\V9\Services\AdGroupCriterionOperation;
use Google\Ads\GoogleAds\V9\Services\AdGroupOperation;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V9\Services\MutateAdGroupAdResult;
use Google\Ads\GoogleAds\V9\Services\MutateAdGroupCriterionResult;
use Google\Ads\GoogleAds\V9\Services\MutateAdGroupResult;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;

/**
 * Class AdsGroup
 *
 * Straight up copying is the sincerest form of flattery.
 * https://developers.google.com/google-ads/api/docs/samples/add-shopping-smart-ad
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsGroup implements OptionsAwareInterface {

	use OptionsAwareTrait;

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
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
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
	 * Delete the additional objects for the specified campaign:
	 * Ad group
	 * Ad group ad
	 * Listing group
	 *
	 * Should only be called before removing the campaign.
	 *
	 * @param string $campaign_resource_name
	 *
	 * @throws ApiException|ValidationException If any object isn't deleted.
	 */
	public function delete_for_campaign( string $campaign_resource_name ) {
		$this->delete_shopping_listing_group( $campaign_resource_name );
		$this->delete_ad_group_ad( $campaign_resource_name );
		$this->delete_ad_group( $campaign_resource_name );
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
	 * @param string $campaign_resource_name
	 *
	 * @return array resource names of deleted ad groups
	 * @throws ApiException If the ad group isn't deleted.
	 * @throws ValidationException If the ad group query has no results.
	 */
	public function delete_ad_group( string $campaign_resource_name ): array {
		$return  = [];
		$results = ( new AdsGroupQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'ad_group.campaign', $campaign_resource_name )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name = $row->getAdGroup()->getResourceName();
			$operation     = new AdGroupOperation();
			$operation->setRemove( $resource_name );
			$deleted_ad_group = $this->mutate_ad_group( $operation );
			$return[]         = $deleted_ad_group->getResourceName();
		}
		return $return;
	}

	/**
	 * @param AdGroupOperation $operation
	 *
	 * @return MutateAdGroupResult
	 * @throws ApiException If the mutate call fails.
	 */
	protected function mutate_ad_group( AdGroupOperation $operation ): MutateAdGroupResult {
		$response = $this->client->getAdGroupServiceClient()->mutateAdGroups(
			$this->options->get_ads_id(),
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
	 * @param string $campaign_resource_name
	 *
	 * @return array resource names of deleted ad group ads
	 * @throws ApiException If the ad group ad isn't deleted.
	 * @throws ValidationException If the ad group ad query has no results.
	 */
	public function delete_ad_group_ad( string $campaign_resource_name ): array {
		$return  = [];
		$results = ( new AdsGroupAdQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'ad_group.campaign', $campaign_resource_name )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name = $row->getAdGroupAd()->getResourceName();
			$operation     = new AdGroupAdOperation();
			$operation->setRemove( $resource_name );
			$deleted_ad_group_ad = $this->mutate_ad_group_ad( $operation );
			$return[]            = $deleted_ad_group_ad->getResourceName();
		}
		return $return;
	}

	/**
	 * @param AdGroupAdOperation $operation
	 *
	 * @return MutateAdGroupAdResult
	 * @throws ApiException If the mutate call fails.
	 */
	protected function mutate_ad_group_ad( AdGroupAdOperation $operation ): MutateAdGroupAdResult {
		$response = $this->client->getAdGroupAdServiceClient()->mutateAdGroupAds(
			$this->options->get_ads_id(),
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
		$created_ad_group_criterion = $this->mutate_shopping_listing_group( $operation );

		return $created_ad_group_criterion->getResourceName();
	}

	/**
	 * @param string $campaign_resource_name
	 *
	 * @return array resource names of deleted shopping list groups
	 * @throws ApiException If the ad group criterion isn't deleted.
	 * @throws ValidationException If the ad group criterion query has no results.
	 */
	protected function delete_shopping_listing_group( string $campaign_resource_name ): array {
		$return  = [];
		$results = ( new AdsListingGroupQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'ad_group.campaign', $campaign_resource_name )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name = $row->getAdGroupCriterion()->getResourceName();
			$operation     = new AdGroupCriterionOperation();
			$operation->setRemove( $resource_name );
			$deleted_ad_group_criterion = $this->mutate_shopping_listing_group( $operation );
			$return[]                   = $deleted_ad_group_criterion->getResourceName();
		}

		return $return;
	}

	/**
	 * @param AdGroupCriterionOperation $operation
	 *
	 * @return MutateAdGroupCriterionResult
	 * @throws ApiException If the mutate call fails.
	 */
	protected function mutate_shopping_listing_group( AdGroupCriterionOperation $operation ): MutateAdGroupCriterionResult {
		$response = $this->client->getAdGroupCriterionServiceClient()->mutateAdGroupCriteria(
			$this->options->get_ads_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}
}
