<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\Util\V23\ResourceNames;
use Google\Ads\GoogleAds\V23\Enums\AssetFieldTypeEnum\AssetFieldType as AssetFieldTypeEnum;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignAssetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsCampaignAssetTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var AdsCampaignAsset $campaign_asset */
	protected $campaign_asset;

	protected const TEST_CAMPAIGN_ID             = 1234567890;
	protected const TEST_BUSINESS_NAME_ID        = 1111111111;
	protected const TEST_LOGO_ID                 = 2222222222;
	protected const TEST_CAMPAIGN_ASSET_RESOURCE = 'customers/12345/campaignAssets/1234567890~1111111111~2';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->campaign_asset = new AdsCampaignAsset( $this->client );
		$this->campaign_asset->set_options_object( $this->options );
	}

	public function test_create_link_operations_returns_expected_operations() {
		$operations = $this->campaign_asset->create_link_operations(
			self::TEST_CAMPAIGN_ID,
			[ self::TEST_BUSINESS_NAME_ID ],
			[ self::TEST_LOGO_ID ]
		);

		$this->assertCount( 2, $operations );

		$expected_campaign_resource = ResourceNames::forCampaign( $this->ads_id, self::TEST_CAMPAIGN_ID );

		$business_name_asset = $operations[0]->getCampaignAssetOperation()->getCreate();
		$this->assertEquals( $expected_campaign_resource, $business_name_asset->getCampaign() );
		$this->assertEquals( ResourceNames::forAsset( $this->ads_id, self::TEST_BUSINESS_NAME_ID ), $business_name_asset->getAsset() );
		$this->assertEquals( AssetFieldTypeEnum::BUSINESS_NAME, $business_name_asset->getFieldType() );

		$logo_asset = $operations[1]->getCampaignAssetOperation()->getCreate();
		$this->assertEquals( $expected_campaign_resource, $logo_asset->getCampaign() );
		$this->assertEquals( ResourceNames::forAsset( $this->ads_id, self::TEST_LOGO_ID ), $logo_asset->getAsset() );
		$this->assertEquals( AssetFieldTypeEnum::LOGO, $logo_asset->getFieldType() );
	}

	public function test_create_link_operations_empty_arrays_returns_no_operations() {
		$operations = $this->campaign_asset->create_link_operations( self::TEST_CAMPAIGN_ID );

		$this->assertEquals( [], $operations );
	}

	public function test_create_link_operations_for_resources_returns_expected_operations() {
		$campaign_resource      = 'customers/12345/campaigns/-1';
		$business_name_resource = 'customers/12345/assets/-7';
		$logo_resource          = 'customers/12345/assets/-8';

		$operations = $this->campaign_asset->create_link_operations_for_resources(
			$campaign_resource,
			[ $business_name_resource ],
			[ $logo_resource ]
		);

		$this->assertCount( 2, $operations );

		$business_name_asset = $operations[0]->getCampaignAssetOperation()->getCreate();
		$this->assertEquals( $campaign_resource, $business_name_asset->getCampaign() );
		$this->assertEquals( $business_name_resource, $business_name_asset->getAsset() );
		$this->assertEquals( AssetFieldTypeEnum::BUSINESS_NAME, $business_name_asset->getFieldType() );

		$logo_asset = $operations[1]->getCampaignAssetOperation()->getCreate();
		$this->assertEquals( $campaign_resource, $logo_asset->getCampaign() );
		$this->assertEquals( $logo_resource, $logo_asset->getAsset() );
		$this->assertEquals( AssetFieldTypeEnum::LOGO, $logo_asset->getFieldType() );
	}

	public function test_create_link_operations_for_resources_empty_arrays_returns_no_operations() {
		$operations = $this->campaign_asset->create_link_operations_for_resources( 'customers/12345/campaigns/-1' );

		$this->assertEquals( [], $operations );
	}

	public function test_create_remove_operation_returns_expected_operation() {
		$operation = $this->campaign_asset->create_remove_operation( self::TEST_CAMPAIGN_ASSET_RESOURCE );

		$this->assertEquals( self::TEST_CAMPAIGN_ASSET_RESOURCE, $operation->getCampaignAssetOperation()->getRemove() );
	}
}
