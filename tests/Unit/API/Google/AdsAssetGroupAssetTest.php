<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroupAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGroupAssetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsAssetGroupAsset          $asset_group_asset
 */
class AdsAssetGroupAssetTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_CAMPAIGN_ID      = 1234567890;
	protected const TEST_ASSET_GROUP_ID   = 5566778899;
	protected const TEST_ASSET_GROUP_ID_2 = 5566778777;
	protected const TEST_ASSET_ID         = 6677889911;
	protected const TEST_ASSET_ID_2       = 4433221100;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->asset   = $this->createMock( AdsAsset::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->asset_group_asset = new AdsAssetGroupAsset( $this->client, $this->asset );
		$this->asset_group_asset->set_options_object( $this->options );
	}



	public function test_get_asset_groups_assets() {
		$asset_1 = [
			'id'      => self::TEST_ASSET_ID,
			'content' => 'Test Asset',
		];

		$asset_2 = [
			'id'      => self::TEST_ASSET_ID_2,
			'content' => 'https://example.com/image.jpg',
		];

		$this->asset->expects( $this->exactly( 2 ) )
		->method( 'convert_asset' )
		->willReturnOnConsecutiveCalls( $asset_1, $asset_2 );

		$asset_group_asset_data = [
			[
				'asset_group_id' => self::TEST_ASSET_GROUP_ID,
				'field_type'     => AssetFieldType::number( AssetFieldType::DESCRIPTION ),
				'asset'          => array_merge( $asset_1, [ 'type' => AssetType::TEXT ] ),
			],
			[
				'asset_group_id' => self::TEST_ASSET_GROUP_ID,
				'field_type'     => AssetFieldType::number( AssetFieldType::MARKETING_IMAGE ),
				'asset'          => array_merge( $asset_2, [ 'type' => AssetType::IMAGE ] ),
			],
		];

		$expected = [
			self::TEST_ASSET_GROUP_ID => [
				AssetFieldType::DESCRIPTION     => [
					[
						'id'      => self::TEST_ASSET_ID,
						'content' => 'Test Asset',
					],
				],
				AssetFieldType::MARKETING_IMAGE => [
					[
						'id'      => self::TEST_ASSET_ID_2,
						'content' => 'https://example.com/image.jpg',
					],
				],
			],

		];

		$this->generate_ads_asset_group_asset_query_mock( $asset_group_asset_data );
		$this->assertEquals( $expected, $this->asset_group_asset->get_assets_by_asset_group_ids( [ self::TEST_CAMPAIGN_ID ] ) );

	}

	public function test_asset_group_assets_without_asset_groups() {
		$this->assertEquals( [], $this->asset_group_asset->get_assets_by_asset_group_ids( [] ) );
	}

	public function test_get_asset_groups_assets_by_final_url() {
		$asset_1 = [
			'id'      => self::TEST_ASSET_ID,
			'content' => 'Test Asset',
		];

		$asset_2 = [
			'id'      => self::TEST_ASSET_ID_2,
			'content' => 'https://example.com/image.jpg',
		];

		$this->asset->expects( $this->exactly( 2 ) )
		->method( 'convert_asset' )
		->willReturnOnConsecutiveCalls( $asset_1, $asset_2 );

		$asset_group_asset_data = [
			[
				'asset_group_id' => self::TEST_ASSET_GROUP_ID,
				'field_type'     => AssetFieldType::number( AssetFieldType::DESCRIPTION ),
				'asset'          => array_merge( $asset_1, [ 'type' => AssetType::TEXT ] ),
				'path1'          => 'path1',
				'path2'          => 'path2',
			],
			[
				'asset_group_id' => self::TEST_ASSET_GROUP_ID_2,
				'field_type'     => AssetFieldType::number( AssetFieldType::MARKETING_IMAGE ),
				'asset'          => array_merge( $asset_2, [ 'type' => AssetType::IMAGE ] ),
				'path1'          => 'path11',
				'path2'          => 'path22',
			],
		];

		$expected = [
			self::TEST_ASSET_GROUP_ID   => [
				AssetFieldType::DESCRIPTION => [
					$asset_1['content'],
				],
				'display_url_path'          => [ 'path1', 'path2' ],
			],
			self::TEST_ASSET_GROUP_ID_2 => [
				AssetFieldType::MARKETING_IMAGE => [
					$asset_2['content'],
				],
				'display_url_path'              => [ 'path11', 'path22' ],
			],

		];

		$this->generate_ads_asset_group_asset_query_mock( $asset_group_asset_data );
		$this->assertEquals( $expected, $this->asset_group_asset->get_assets_by_final_url( 'https://example.com' ) );

	}



	public function test_edit_asset_group_assets_with_empty_assets() {
		$this->assertEquals( [], $this->asset_group_asset->edit_operations( self::TEST_ASSET_GROUP_ID, [] ) );
	}

	public function test_edit_asset_group_assets_with_update_assets() {
		$assets = [
			[
				'id'         => self::TEST_ASSET_ID,
				'field_type' => AssetFieldType::DESCRIPTION,
				'content'    => 'Test Asset',
			],
			[
				'id'         => self::TEST_ASSET_ID_2,
				'field_type' => AssetFieldType::HEADLINE,
				'content'    => 'https://example.com/image.jpg',
			],
		];

		$this->asset->expects( $this->exactly( 2 ) )
		->method( 'create_operation' )
		->willReturnOnConsecutiveCalls( ...$this->generate_crate_asset_operations( $assets ) );

		$grouped_operations = $this->group_operations(
			$this->asset_group_asset->edit_operations( self::TEST_ASSET_GROUP_ID, $assets )
		);

		// We should have two type of operations: asset_operation and asset_group_asset_operation
		$this->assertEquals( 2, count( $grouped_operations ) );

		// We should have two assets creation.
		$this->assertEquals( 2, count( $grouped_operations['asset_operation']['create'] ) );
		$this->assertEquals( 2, count( $grouped_operations['asset_group_asset_operation']['create'] ) );

		$this->assertEquals( $assets[0]['content'], ( $grouped_operations['asset_operation']['create'][0] )->getCreate()->getTextAsset()->getText() );
		$this->assertEquals( $assets[1]['content'], ( $grouped_operations['asset_operation']['create'][1] )->getCreate()->getTextAsset()->getText() );

		$this->assertEquals( AssetFieldType::number( AssetFieldType::DESCRIPTION ), ( $grouped_operations['asset_group_asset_operation']['create'][0] )->getCreate()->getFieldType() );
		$this->assertEquals( AssetFieldType::number( AssetFieldType::HEADLINE ), ( $grouped_operations['asset_group_asset_operation']['create'][1] )->getCreate()->getFieldType() );

		// We should remove the two old assets.
		$this->assertEquals( 2, count( $grouped_operations['asset_group_asset_operation']['remove'] ) );

		$this->assertEquals( ResourceNames::forAssetGroupAsset( $this->options->get_ads_id(), self::TEST_ASSET_GROUP_ID, $assets[0]['id'], AssetFieldType::name( $assets[0]['field_type'] ) ), ( $grouped_operations['asset_group_asset_operation']['remove'][0] )->getRemove() );
		$this->assertEquals( ResourceNames::forAssetGroupAsset( $this->options->get_ads_id(), self::TEST_ASSET_GROUP_ID, $assets[1]['id'], AssetFieldType::name( $assets[1]['field_type'] ) ), ( $grouped_operations['asset_group_asset_operation']['remove'][1] )->getRemove() );

	}

	public function test_edit_asset_group_assets_create_assets() {
		$assets = [
			[
				'id'         => null,
				'field_type' => AssetFieldType::DESCRIPTION,
				'content'    => 'Test Asset',
			],
			[
				'id'         => null,
				'field_type' => AssetFieldType::HEADLINE,
				'content'    => 'https://example.com/image.jpg',
			],
		];

		$this->asset->expects( $this->exactly( 2 ) )
		->method( 'create_operation' )
		->willReturnOnConsecutiveCalls( ...$this->generate_crate_asset_operations( $assets ) );

		$grouped_operations = $this->group_operations(
			$this->asset_group_asset->edit_operations( self::TEST_ASSET_GROUP_ID, $assets )
		);

		// We should have two type of operations: asset_operation and asset_group_asset_operation
		$this->assertEquals( 2, count( $grouped_operations ) );

		// We should have two assets creation.
		$this->assertEquals( 2, count( $grouped_operations['asset_operation']['create'] ) );
		$this->assertEquals( $assets[0]['content'], ( $grouped_operations['asset_operation']['create'][0] )->getCreate()->getTextAsset()->getText() );
		$this->assertEquals( $assets[1]['content'], ( $grouped_operations['asset_operation']['create'][1] )->getCreate()->getTextAsset()->getText() );

		$this->assertEquals( 2, count( $grouped_operations['asset_group_asset_operation']['create'] ) );
		$this->assertEquals( AssetFieldType::number( AssetFieldType::DESCRIPTION ), ( $grouped_operations['asset_group_asset_operation']['create'][0] )->getCreate()->getFieldType() );
		$this->assertEquals( AssetFieldType::number( AssetFieldType::HEADLINE ), ( $grouped_operations['asset_group_asset_operation']['create'][1] )->getCreate()->getFieldType() );

		// We should not remove old assets.
		$this->assertArrayNotHasKey( 'remove', $grouped_operations['asset_group_asset_operation'] );

	}

	public function test_edit_asset_group_assets_delete_assets() {
		$assets = [
			[
				'id'         => self::TEST_ASSET_ID,
				'field_type' => AssetFieldType::DESCRIPTION,
				'content'    => null,
			],
			[
				'id'         => self::TEST_ASSET_ID_2,
				'field_type' => AssetFieldType::HEADLINE,
				'content'    => null,
			],
		];

		$this->asset->expects( $this->exactly( 0 ) )
		->method( 'create_operation' );

		$grouped_operations = $this->group_operations(
			$this->asset_group_asset->edit_operations( self::TEST_ASSET_GROUP_ID, $assets )
		);

		// We should have one type of operations: asset_operation.
		$this->assertEquals( 1, count( $grouped_operations ) );

		$this->assertArrayNotHasKey( 'asset_operation', $grouped_operations );

		// We should have two delete asset_group_asset_operation.
		$this->assertEquals( 2, count( $grouped_operations['asset_group_asset_operation']['remove'] ) );
		$this->assertEquals( ResourceNames::forAssetGroupAsset( $this->options->get_ads_id(), self::TEST_ASSET_GROUP_ID, $assets[0]['id'], AssetFieldType::name( $assets[0]['field_type'] ) ), ( $grouped_operations['asset_group_asset_operation']['remove'][0] )->getRemove() );
		$this->assertEquals( ResourceNames::forAssetGroupAsset( $this->options->get_ads_id(), self::TEST_ASSET_GROUP_ID, $assets[1]['id'], AssetFieldType::name( $assets[1]['field_type'] ) ), ( $grouped_operations['asset_group_asset_operation']['remove'][1] )->getRemove() );

		// We should not create assets.
		$this->assertArrayNotHasKey( 'create', $grouped_operations['asset_group_asset_operation'] );

	}

	private function group_operations( $operations ) {
		$grouped_operations = [];

		foreach ( $operations as $operation ) {

			$operation_name = $operation->getOperation();

			if ( $operation_name === 'asset_operation' ) {
				$operation = $operation->getAssetOperation();
			} elseif ( $operation_name === 'asset_group_asset_operation' ) {
				$operation = $operation->getAssetGroupAssetOperation();
			} else {
				$operation = null;
			}

			$grouped_operations[ $operation_name ][ $operation->getOperation() ][] = $operation;

		}

		return $grouped_operations;
	}


}
