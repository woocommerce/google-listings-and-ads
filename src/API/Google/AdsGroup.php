<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsGroupQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsGroupAdQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsListingGroupQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\V9\ResourceNames;
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
use Google\Ads\GoogleAds\V9\Services\MutateOperation;

/**
 * Class AdsGroup
 *
 * @since x.x.x Refactored to support deleting of a legacy SSC Ad Group.
 *
 * Used for legacy Smart Shopping Campaigns (SSC)
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
	 * List of ad group resource names.
	 *
	 * @var string[]
	 */
	protected $ad_groups;

	/**
	 * AdsGroup constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Returns a set of operations to delete an ad group.
	 *
	 * @param string $campaign_resource_name
	 * @return array
	 */
	public function delete_operations( string $campaign_resource_name ): array {
		$ad_group_operations      = $this->ad_group_delete_operations( $campaign_resource_name );
		$ad_group_ad_operations   = $this->ad_group_ad_delete_operations( $campaign_resource_name );
		$listing_group_operations = $this->listing_group_delete_operations();

		// All assets must be deleted from the group before deleting the ad group.
		return array_merge(
			$listing_group_operations,
			$ad_group_ad_operations,
			$ad_group_operations
		);
	}

	/**
	 * Returns an ad group delete operation.
	 *
	 * @param string $campaign_resource_name
	 *
	 * @return MutateOperation[]
	 */
	protected function ad_group_delete_operations( string $campaign_resource_name ): array {
		$operations      = [];
		$this->ad_groups = [];

		$results = ( new AdsGroupQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'ad_group.campaign', $campaign_resource_name )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name     = $row->getAdGroup()->getResourceName();
			$this->ad_groups[] = $resource_name;
			$operation         = ( new AdGroupOperation() )->setRemove( $resource_name );
			$operations[]      = ( new MutateOperation() )->setAdGroupOperation( $operation );
		}

		return $operations;
	}

	/**
	 * Returns an ad group ad delete operation.
	 *
	 * @return MutateOperation[]
	 */
	protected function ad_group_ad_delete_operations(): array {
		if ( empty( $this->ad_groups ) ) {
			return [];
		}

		$operations = [];
		$results    = ( new AdsGroupAdQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'ad_group_ad.ad_group', $this->ad_groups, 'IN' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name = $row->getAdGroupAd()->getResourceName();
			$operation     = ( new AdGroupAdOperation() )->setRemove( $resource_name );
			$operations[]  = ( new MutateOperation() )->setAdGroupAdOperation( $operation );
		}

		return $operations;
	}

	/**
	 * Returns an ad group listing group delete operation.
	 *
	 * @return MutateOperation[]
	 */
	protected function listing_group_delete_operations(): array {
		if ( empty( $this->ad_groups ) ) {
			return [];
		}

		$operations = [];
		$results    = ( new AdsListingGroupQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'ad_group_criterion.ad_group', $this->ad_groups, 'IN' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$resource_name = $row->getAdGroupCriterion()->getResourceName();
			$operation     = ( new AdGroupCriterionOperation() )->setRemove( $resource_name );
			$operations[]  = ( new MutateOperation() )->setAdGroupCriterionOperation( $operation );
		}

		return $operations;
	}
}
