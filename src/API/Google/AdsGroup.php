<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

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
use Google\Ads\GoogleAds\V9\Services\MutateOperation;

/**
 * Class AdsGroup
 *
 * @since 1.12.0 Refactored to use batch requests when operating on campaigns.
 *
 * Used for Smart Shopping Campaigns (SSC)
 * https://developers.google.com/google-ads/api/docs/samples/add-shopping-smart-ad
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsGroup implements OptionsAwareInterface {

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
	 * Returns a set of operations to create an ad group.
	 *
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 * @return array
	 */
	public function create_operations( string $campaign_resource_name, string $campaign_name ): array {
		// Ad group must be created before listing group.
		return [
			$this->ad_group_create_operation( $campaign_resource_name, $campaign_name ),
			$this->ad_group_ad_create_operation(),
			$this->listing_group_create_operation(),
		];
	}

	/**
	 * Returns an ad group create operation.
	 *
	 * @param string $campaign_resource_name
	 * @param string $campaign_name
	 *
	 * @return MutateOperation
	 */
	protected function ad_group_create_operation( string $campaign_resource_name, string $campaign_name ): MutateOperation {
		$ad_group = new AdGroup(
			[
				'resource_name' => $this->temporary_resource_name(),
				'name'          => $campaign_name . ' Ad Group',
				'campaign'      => $campaign_resource_name,
				'type'          => AdGroupType::SHOPPING_SMART_ADS,
				'status'        => AdGroupStatus::ENABLED,
			]
		);

		$operation = ( new AdGroupOperation() )->setCreate( $ad_group );
		return ( new MutateOperation() )->setAdGroupOperation( $operation );
	}

	/**
	 * Returns an ad group ad create operation.
	 *
	 * @return MutateOperation
	 */
	protected function ad_group_ad_create_operation(): MutateOperation {
		$ad_group_ad = new AdGroupAd(
			[
				'ad'       => new Ad( [ 'shopping_smart_ad' => new ShoppingSmartAdInfo() ] ),
				'ad_group' => $this->temporary_resource_name(),
			]
		);

		$operation = ( new AdGroupAdOperation() )->setCreate( $ad_group_ad );
		return ( new MutateOperation() )->setAdGroupAdOperation( $operation );
	}

	/**
	 * Returns an ad group criterion create operation.
	 *
	 * @return MutateOperation
	 */
	protected function listing_group_create_operation(): MutateOperation {
		$ad_group_criterion = new AdGroupCriterion(
			[
				'ad_group'      => $this->temporary_resource_name(),
				'status'        => AdGroupAdStatus::ENABLED,
				'listing_group' => new ListingGroupInfo( [ 'type' => ListingGroupType::UNIT ] ),
			]
		);

		$operation = ( new AdGroupCriterionOperation() )->setCreate( $ad_group_criterion );
		return ( new MutateOperation() )->setAdGroupCriterionOperation( $operation );
	}

	/**
	 * Return a temporary resource name for the ad group.
	 *
	 * @return string
	 */
	protected function temporary_resource_name() {
		return ResourceNames::forAdGroup( $this->options->get_ads_id(), self::TEMPORARY_ID );
	}
}
