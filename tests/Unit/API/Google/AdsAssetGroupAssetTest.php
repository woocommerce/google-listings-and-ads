<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroupAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Google\Ads\GoogleAds\V11\Enums\AssetFieldTypeEnum\AssetFieldType;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGroupAssetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsAssetGroup               $asset_group
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

		$this->asset   = new AdsAsset();
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->asset_group_asset = new AdsAssetGroupAsset( $this->client, $this->asset );
		$this->asset_group_asset->set_options_object( $this->options );
	}



	public function test_get_asset_groups_assets() {
		$asset_group_asset_data = [
			[
				'asset_group_id' => self::TEST_ASSET_GROUP_ID,
				'field_type'     => AssetFieldType::DESCRIPTION,
				'asset'          => [
					'id'   => self::TEST_ASSET_ID,
					'type' => AssetType::TEXT,
					'text' => 'Test Asset',
				],
			],
			[
				'asset_group_id' => self::TEST_ASSET_GROUP_ID,
				'field_type'     => AssetFieldType::MARKETING_IMAGE,
				'asset'          => [
					'id'        => self::TEST_ASSET_ID_2,
					'type'      => AssetType::IMAGE,
					'image_url' => 'https://example.com/image.jpg',
				],
			],
		];

		$expected = [
			self::TEST_ASSET_GROUP_ID => [
				strtolower( AssetFieldType::name( AssetFieldType::DESCRIPTION ) ) => [
					[
						'id'      => self::TEST_ASSET_ID,
						'content' => 'Test Asset',
					],
				],
				strtolower( AssetFieldType::name( AssetFieldType::MARKETING_IMAGE ) ) => [
					[
						'id'      => self::TEST_ASSET_ID_2,
						'content' => 'https://example.com/image.jpg',
					],
				],
			],

		];

		$this->generate_ads_asset_group_asset_query_mock( $asset_group_asset_data );
		$this->assertEquals( $expected, $this->asset_group_asset->get_asset_group_assets( [ self::TEST_CAMPAIGN_ID ] ) );

	}

	public function test_asset_group_assets_without_asset_groups() {
		$this->assertEquals( [], $this->asset_group_asset->get_asset_group_assets( [] ) );
	}



}
