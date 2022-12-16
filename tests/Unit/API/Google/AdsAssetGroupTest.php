<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroupAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\V11\Enums\AssetGroupStatusEnum\AssetGroupStatus;
use Google\Ads\GoogleAds\V11\Enums\ListingGroupFilterTypeEnum\ListingGroupFilterType;
use Google\Ads\GoogleAds\V11\Enums\ListingGroupFilterVerticalEnum\ListingGroupFilterVertical;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGroupTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsAssetGroup               $asset_group
 */
class AdsAssetGroupTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_CAMPAIGN_ID      = 1234567890;
	protected const TEST_ASSET_GROUP_ID   = 5566778899;
	protected const TEST_ASSET_GROUP_ID_2 = 5566778777;
	protected const TEST_LISTING_GROUP_ID = 6677889911;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->asset_group_asset = $this->createMock( AdsAssetGroupAsset::class );
		$this->options           = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->asset_group = new AdsAssetGroup( $this->client, $this->asset_group_asset );
		$this->asset_group->set_options_object( $this->options );
	}

	public function test_create_operations() {
		$campaign_resource_name    = $this->generate_campaign_resource_name( self::TEST_CAMPAIGN_ID );
		$asset_group_resource_name = $this->generate_asset_group_resource_name( -3 );

		$operations = $this->asset_group->create_operations(
			$campaign_resource_name,
			'New Campaign'
		);

		$operation_asset_group = $operations[0]->getAssetGroupOperation();
		$this->assertTrue( $operation_asset_group->hasCreate() );

		$asset_group = $operation_asset_group->getCreate();
		$this->assertEquals( 'New Campaign Asset Group', $asset_group->getName() );
		$this->assertEquals( $campaign_resource_name, $asset_group->getCampaign() );
		$this->assertEquals( $asset_group_resource_name, $asset_group->getResourceName() );
		$this->assertEquals( AssetGroupStatus::ENABLED, $asset_group->getStatus() );

		$operation_listing_group = $operations[1]->getAssetGroupListingGroupFilterOperation();
		$this->assertTrue( $operation_listing_group->hasCreate() );

		$listing_group = $operation_listing_group->getCreate();
		$this->assertEquals( 'New Campaign Asset Group', $asset_group->getName() );
		$this->assertEquals( $asset_group_resource_name, $listing_group->getAssetGroup() );
		$this->assertEquals( ListingGroupFilterType::UNIT_INCLUDED, $listing_group->getType() );
		$this->assertEquals( ListingGroupFilterVertical::SHOPPING, $listing_group->getVertical() );
	}

	public function test_get_asset_groups_by_campaign_id_with_assets() {
		$assets_data = [
			self::TEST_ASSET_GROUP_ID   => [
				'description' => [
					'id'      => 22222,
					'content' => 'description 1',
				],

			],
			self::TEST_ASSET_GROUP_ID_2 => [
				'headline' => [
					'id'      => 33333,
					'content' => 'description 1',
				],
			],
		];

		$asset_group_data = [
			[
				'id'               => self::TEST_ASSET_GROUP_ID,
				'final_url'        => 'https://www.example.com',
				'display_url_path' => [ 'mypath1', 'mypath2' ],
				'assets'           => $assets_data[ self::TEST_ASSET_GROUP_ID ],
			],
			[
				'id'               => self::TEST_ASSET_GROUP_ID_2,
				'final_url'        => 'https://www.example2.com',
				'display_url_path' => [ 'mypath2_example1', 'mypath2_example2' ],
				'assets'           => $assets_data[ self::TEST_ASSET_GROUP_ID_2 ],
			],
		];

		$this->asset_group_asset->expects( $this->exactly( 1 ) )
			->method( 'get_asset_group_assets' )
			->with( [ self::TEST_ASSET_GROUP_ID, self::TEST_ASSET_GROUP_ID_2 ] )
			->willReturn( $assets_data );

		$this->generate_ads_asset_groups_query_mock( $asset_group_data );
		$this->assertEquals( $asset_group_data, $this->asset_group->get_asset_groups_by_campaign_id( self::TEST_CAMPAIGN_ID ) );

	}

	public function test_get_asset_groups_by_campaign_id_without_assets() {
		$include_assets   = false;
		$asset_group_data = [
			[
				'id'               => self::TEST_ASSET_GROUP_ID,
				'final_url'        => 'https://www.example.com',
				'display_url_path' => [ 'mypath1', 'mypath2' ],
			],
			[
				'id'               => self::TEST_ASSET_GROUP_ID_2,
				'final_url'        => 'https://www.example2.com',
				'display_url_path' => [ 'mypath2_example1', 'mypath2_example2' ],
			],
		];

		$this->asset_group_asset->expects( $this->exactly( 0 ) )
			->method( 'get_asset_group_assets' );

		$this->generate_ads_asset_groups_query_mock( $asset_group_data );
		$this->assertEquals( $asset_group_data, $this->asset_group->get_asset_groups_by_campaign_id( self::TEST_CAMPAIGN_ID, $include_assets ) );

	}



}
