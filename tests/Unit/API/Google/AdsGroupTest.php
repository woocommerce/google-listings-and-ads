<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\V9\Common\ShoppingSmartAdInfo;
use Google\Ads\GoogleAds\V9\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V9\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\Ads\GoogleAds\V9\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V9\Enums\ListingGroupTypeEnum\ListingGroupType;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsGroupTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsGroup                    $ad_group
 */
class AdsGroupTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_CAMPAIGN_ID      = 1234567890;
	protected const TEST_AD_GROUP_ID      = 5566778899;
	protected const TEST_AD_GROUP_AD_ID   = 6677889911;
	protected const TEST_LISTING_GROUP_ID = 7788991122;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->ad_group = new AdsGroup( $this->client );
		$this->ad_group->set_options_object( $this->options );
	}

	public function test_create_operations() {
		$campaign_resource_name = $this->generate_campaign_resource_name( self::TEST_CAMPAIGN_ID );
		$ad_group_resource_name = $this->generate_ad_group_resource_name( -3 );

		$operations = $this->ad_group->create_operations(
			$campaign_resource_name,
			'New Campaign'
		);

		$operation_ad_group = $operations[0]->getAdGroupOperation();
		$this->assertTrue( $operation_ad_group->hasCreate() );

		$ad_group = $operation_ad_group->getCreate();
		$this->assertEquals( 'New Campaign Ad Group', $ad_group->getName() );
		$this->assertEquals( $campaign_resource_name, $ad_group->getCampaign() );
		$this->assertEquals( $ad_group_resource_name, $ad_group->getResourceName() );
		$this->assertEquals( AdGroupStatus::ENABLED, $ad_group->getStatus() );
		$this->assertEquals( AdGroupType::SHOPPING_SMART_ADS, $ad_group->getType() );

		$operation_ad_group_ad = $operations[1]->getAdGroupAdOperation();
		$this->assertTrue( $operation_ad_group_ad->hasCreate() );

		$ad_group_ad = $operation_ad_group_ad->getCreate();
		$this->assertEquals( $ad_group_resource_name, $ad_group_ad->getAdGroup() );
		$this->assertEquals( new ShoppingSmartAdInfo(), $ad_group_ad->getAd()->getShoppingSmartAd() );

		$operation_listing_group = $operations[2]->getAdGroupCriterionOperation();
		$this->assertTrue( $operation_listing_group->hasCreate() );

		$listing_group = $operation_listing_group->getCreate();
		$this->assertEquals( $ad_group_resource_name, $listing_group->getAdGroup() );
		$this->assertEquals( AdGroupAdStatus::ENABLED, $listing_group->getStatus() );
		$this->assertEquals(
			ListingGroupType::UNIT,
			$listing_group->getListingGroup()->getType()
		);
	}
}
