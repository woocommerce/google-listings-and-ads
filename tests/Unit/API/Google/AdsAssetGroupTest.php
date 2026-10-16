<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroupAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\V23\Enums\AssetGroupStatusEnum\AssetGroupStatus;
use Google\Ads\GoogleAds\V23\Enums\ListingGroupFilterListingSourceEnum\ListingGroupFilterListingSource;
use Google\Ads\GoogleAds\V23\Enums\ListingGroupFilterTypeEnum\ListingGroupFilterType;
use PHPUnit\Framework\MockObject\MockObject;
use Google\ApiCore\ApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGroupTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsAssetGroupTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|AdsAssetGroupAsset $asset_group_asset */
	protected $asset_group_asset;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var Container $container */
	protected $container;

	/** @var AdsAsset $asset */
	protected $asset;

	/** @var AdsAssetGroup $asset_group */
	protected $asset_group;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

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
		$this->ads_campaign      = $this->createMock( AdsCampaign::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->asset = $this->createMock( AdsAsset::class );

		$this->container = new Container();
		$this->container->addShared( AdsAsset::class, $this->asset );

		$this->asset_group = new AdsAssetGroup( $this->client, $this->asset_group_asset, $this->ads_campaign );
		$this->asset_group->set_options_object( $this->options );
		$this->asset_group->set_container( $this->container );
	}

	public function test_create_operations_with_merchant_center() {
		// Mock merchant_id > 0 to simulate Merchant Center connected
		$this->options->method( 'get_merchant_id' )->willReturn( 12345 );

		$campaign_resource_name    = $this->generate_campaign_resource_name( self::TEST_CAMPAIGN_ID );
		$asset_group_resource_name = $this->generate_asset_group_resource_name( -3 );

		$operations = $this->asset_group->create_operations(
			$campaign_resource_name,
			'New Campaign'
		);

		// Should return 2 operations: asset group + listing group filter
		$this->assertCount( 2, $operations );

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
		$this->assertEquals( $asset_group_resource_name, $listing_group->getAssetGroup() );
		$this->assertEquals( ListingGroupFilterType::UNIT_INCLUDED, $listing_group->getType() );
		$this->assertEquals( ListingGroupFilterListingSource::SHOPPING, $listing_group->getListingSource() );
	}

	public function test_get_asset_groups_by_campaign_id_with_assets() {
		$assets_data = [
			self::TEST_ASSET_GROUP_ID   => [
				'description' => [
					'id'      => 22222,
					'content' => 'description text',
				],
				'headline'    => [
					'id'      => 33333,
					'content' => 'headline text',
				],
			],
			self::TEST_ASSET_GROUP_ID_2 => [
				'headline' => [
					'id'      => 44444,
					'content' => 'headline text',
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
			->method( 'get_assets_by_asset_group_ids' )
			->with( [ self::TEST_ASSET_GROUP_ID ] ) // Only fetch assets for first group.
			->willReturn( $assets_data );

		$this->generate_ads_asset_groups_query_mock( $asset_group_data );
		$this->assertEquals(
			array_slice( $asset_group_data, 0, 1 ), // Only expect one asset group.
			$this->asset_group->get_asset_groups_by_campaign_id( self::TEST_CAMPAIGN_ID )
		);
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
			->method( 'get_assets_by_asset_group_ids' );

		$this->generate_ads_asset_groups_query_mock( $asset_group_data );
		$this->assertEquals(
			array_slice( $asset_group_data, 0, 1 ), // Only expect one asset group.
			$this->asset_group->get_asset_groups_by_campaign_id( self::TEST_CAMPAIGN_ID, $include_assets )
		);
	}

	public function test_edit_asset_group_with_asset() {
		$asset = [
			'id'         => 11111,
			'field_type' => AssetFieldType::DESCRIPTION,
			'content'    => 'desc1',
		];

		$asset_group_data = [
			'final_url' => 'https://www.example.com',
			'path1'     => 'mypath1',
			'path2'     => 'mypath2',
		];

		$this->asset_group_asset->expects( $this->exactly( 1 ) )
			->method( 'edit_operations' )
			->with( self::TEST_ASSET_GROUP_ID, [ $asset ], $this->isType( 'bool' ) )
			->willReturn(
				[
					'operations'                   => $this->generate_create_asset_group_asset_operations(
						[
							[
								'asset_id'       => $asset['id'],
								'asset_group_id' => self::TEST_ASSET_GROUP_ID,
								'field_type'     => $asset['field_type'],
							],
						]
					),
					'assets_for_creation'          => [],
					'created_asset_resource_names' => [],
				]
			);

		$this->generate_ads_query_mock( [] );
		$this->generate_asset_group_mutate_mock( 'update', self::TEST_ASSET_GROUP_ID, true );

		$this->assertEquals(
			self::TEST_ASSET_GROUP_ID,
			$this->asset_group->edit_asset_group( self::TEST_ASSET_GROUP_ID, $asset_group_data, [ $asset ] )
		);
	}

	public function test_edit_asset_group_without_assets() {
		$asset_group_data = [
			'path1' => 'mypath1',
			'path2' => 'mypath2',
		];

		$this->asset_group_asset->expects( $this->once() )
			->method( 'edit_operations' )
			->with( self::TEST_ASSET_GROUP_ID, [], $this->isType( 'bool' ) )
			->willReturn(
				[
					'operations'                   => [],
					'assets_for_creation'          => [],
					'created_asset_resource_names' => [],
				]
			);

		$this->generate_ads_query_mock( [] );
		$this->generate_asset_group_mutate_mock( 'update', self::TEST_ASSET_GROUP_ID );

		$this->assertEquals(
			self::TEST_ASSET_GROUP_ID,
			$this->asset_group->edit_asset_group( self::TEST_ASSET_GROUP_ID, $asset_group_data )
		);
	}

	public function test_edit_asset_group_exception() {
		$asset_group_data = [
			'path2' => 123456,
		];

		$this->asset_group_asset->expects( $this->once() )
			->method( 'edit_operations' )
			->with( self::TEST_ASSET_GROUP_ID, [], $this->isType( 'bool' ) )
			->willReturn(
				[
					'operations'      => [],
					'brand_asset_ids' =>
						[
							'business_name' => [],
							'logo'          => [],
						],
				]
			);

		$this->generate_ads_query_mock( [] );
		$this->generate_mutate_mock_exception( new ApiException( 'invalid', 3, 'INVALID_ARGUMENT' ) );

		try {
			$this->asset_group->edit_asset_group( self::TEST_ASSET_GROUP_ID, $asset_group_data );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error editing asset group: invalid',
					'errors'  => [ 'INVALID_ARGUMENT' => 'invalid' ],
					'id'      => self::TEST_ASSET_GROUP_ID,
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 400, $e->getCode() );
		}
	}

	public function test_edit_asset_group_request_too_long() {
		$asset_group_data = [
			'path2' => 123456,
		];

		$this->asset_group_asset->expects( $this->once() )
			->method( 'edit_operations' )
			->with( self::TEST_ASSET_GROUP_ID, [], $this->isType( 'bool' ) )
			->willReturn(
				[
					'operations'                   => [],
					'assets_for_creation'          => [],
					'created_asset_resource_names' => [],
				]
			);

		$this->generate_ads_query_mock( [] );
		$this->generate_mutate_mock_exception( new ApiException( 'Request entity too large', 413, 'UNRECOGNIZED_STATUS' ) );

		try {
			$this->asset_group->edit_asset_group( self::TEST_ASSET_GROUP_ID, $asset_group_data );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error editing asset group: Request entity too large',
					'errors'  => [ 'Request entity too large' ],
					'id'      => self::TEST_ASSET_GROUP_ID,
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 413, $e->getCode() );
		}
	}

	public function test_create_asset_group_with_merchant_center() {
		// Mock merchant_id > 0 to simulate Merchant Center connected
		$this->options->method( 'get_merchant_id' )->willReturn( 12345 );

		$this->generate_asset_group_mutate_mock( 'create', self::TEST_CAMPAIGN_ID );

		$this->assertEquals(
			self::TEST_CAMPAIGN_ID,
			$this->asset_group->create_asset_group( self::TEST_CAMPAIGN_ID )
		);
	}

	public function test_create_asset_group_exception() {
		$this->generate_mutate_mock_exception( new ApiException( 'invalid', 3, 'INVALID_ARGUMENT' ) );

		try {
			$this->asset_group->create_asset_group( self::TEST_ASSET_GROUP_ID );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error creating asset group: invalid',
					'errors'  => [ 'INVALID_ARGUMENT' => 'invalid' ],
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 400, $e->getCode() );
		}
	}

	public function test_create_create_operations_with_assets_returns_expected_operations() {
		$campaign_resource_name = $this->generate_campaign_resource_name( self::TEST_CAMPAIGN_ID );
		$asset_group_assets     = [
			[
				'field_type' => AssetFieldType::HEADLINE,
				'content'    => 'Test headline',
			],
			[
				'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
				'content'    => 'https://example.com/image.jpg',
			],
		];

		$this->asset->expects( $this->once() )
			->method( 'create_operations' )
			->with( $asset_group_assets )
			->willReturn(
				[
					$this->generate_asset_create_operation(
						-1,
						$asset_group_assets[0]['field_type'],
						$asset_group_assets[0]['content'],
					),
					$this->generate_asset_create_operation(
						-2,
						$asset_group_assets[1]['field_type'],
						$asset_group_assets[1]['content'],
					),
				]
			);

		$operations = $this->asset_group->create_operations_with_assets(
			$campaign_resource_name,
			'New Campaign',
			'https://example.com',
			$asset_group_assets
		);

		$this->assertCount( 5, $operations );
		$this->assertTrue( $operations[0]->hasAssetGroupOperation() );
		$this->assertTrue( $operations[1]->hasAssetOperation() );
		$this->assertTrue( $operations[2]->hasAssetOperation() );
		$this->assertTrue( $operations[3]->hasAssetGroupAssetOperation() );
		$this->assertTrue( $operations[4]->hasAssetGroupAssetOperation() );
	}
}
