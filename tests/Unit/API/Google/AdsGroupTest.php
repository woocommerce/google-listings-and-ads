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
	public function setUp() {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->ad_group = new AdsGroup( $this->client );
		$this->ad_group->set_options_object( $this->options );
	}

	public function test_delete_operations() {
		$campaign_resource_name      = $this->generate_campaign_resource_name( self::TEST_CAMPAIGN_ID );
		$ad_group_resource_name      = $this->generate_ad_group_resource_name( self::TEST_AD_GROUP_ID );
		$ad_group_ad_resource_name   = $this->generate_ad_group_ad_resource_name( self::TEST_AD_GROUP_ID, self::TEST_AD_GROUP_AD_ID );
		$listing_group_resource_name = $this->generate_ad_group_criterion_resource_name( self::TEST_AD_GROUP_ID, self::TEST_LISTING_GROUP_ID );

		$this->generate_ads_group_query_mock(
			$ad_group_resource_name,
			$ad_group_ad_resource_name,
			$listing_group_resource_name
		);

		$operations = $this->ad_group->delete_operations(
			$campaign_resource_name
		);

		$operation_listing_group = $operations[0]->getAdGroupCriterionOperation();
		$this->assertTrue( $operation_listing_group->hasRemove() );
		$this->assertEquals(
			$listing_group_resource_name,
			$operation_listing_group->getRemove()
		);

		$operation_ad_group_ad = $operations[1]->getAdGroupAdOperation();
		$this->assertTrue( $operation_ad_group_ad->hasRemove() );
		$this->assertEquals(
			$ad_group_ad_resource_name,
			$operation_ad_group_ad->getRemove()
		);

		$operation_ad_group = $operations[2]->getAdGroupOperation();
		$this->assertTrue( $operation_ad_group->hasRemove() );
		$this->assertEquals(
			$ad_group_resource_name,
			$operation_ad_group->getRemove()
		);
	}
}
