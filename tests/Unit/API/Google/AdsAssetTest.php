<?php
declare( strict_types=0 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CallToActionType;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;
use Exception;


defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsAsset                    $asset
 */
class AdsAssetTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEMPORARY_ID  = -6;
	protected const TEST_ASSET_ID = 6677889911;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->asset = new AdsAsset();
		$this->asset->set_options_object( $this->options );
	}

	public function test_convert_asset_text() {
		$data = [
			'field_type' => AssetFieldType::HEADLINE,
			'content'    => 'Test headline',
			'id'         => self::TEST_ASSET_ID,
		];
		$row  = $this->generate_asset_row_mock( $data );

		$expected = [
			'id'      => self::TEST_ASSET_ID,
			'content' => $data['content'],
		];

		$this->assertEquals( $expected, $this->asset->convert_asset( $row ) );
	}

	public function test_convert_asset_image() {
		$data = [
			'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
			'content'    => 'https://example.com/image.jpg',
			'id'         => self::TEST_ASSET_ID,
		];
		$row  = $this->generate_asset_row_mock( $data );

		$expected = [
			'id'      => self::TEST_ASSET_ID,
			'content' => $data['content'],
		];

		$this->assertEquals( $expected, $this->asset->convert_asset( $row ) );
	}

	public function test_convert_asset_call_action() {
		$data = [
			'field_type' => AssetFieldType::CALL_TO_ACTION_SELECTION,
			'content'    => CallToActionType::SHOP_NOW,
			'id'         => self::TEST_ASSET_ID,
		];
		$row  = $this->generate_asset_row_mock( $data );

		$expected = [
			'id'      => self::TEST_ASSET_ID,
			'content' => $data['content'],
		];

		$this->assertEquals( $expected, $this->asset->convert_asset( $row ) );
	}

	public function test_create_operation_text_asset() {
		$data = [
			'field_type' => AssetFieldType::HEADLINE,
			'content'    => 'Test headline',
		];

		$operation       = $this->asset->create_operation( $data, self::TEMPORARY_ID );
		$asset_operation = $operation->getAssetOperation();

		$this->assertTrue( $asset_operation->hasCreate() );
		$this->assertEquals( ResourceNames::forAsset( $this->options->get_ads_id(), self::TEMPORARY_ID ), $asset_operation->getCreate()->getResourceName() );
		$this->assertEquals( $data['content'], $asset_operation->getCreate()->getTextAsset()->getText() );

	}

	public function test_create_operation_call_to_action_asset() {
		$data = [
			'field_type' => AssetFieldType::CALL_TO_ACTION_SELECTION,
			'content'    => CallToActionType::SHOP_NOW,
		];

		$operation       = $this->asset->create_operation( $data, self::TEMPORARY_ID );
		$asset_operation = $operation->getAssetOperation();

		$this->assertTrue( $asset_operation->hasCreate() );
		$this->assertEquals( ResourceNames::forAsset( $this->options->get_ads_id(), self::TEMPORARY_ID ), $asset_operation->getCreate()->getResourceName() );
		$this->assertEquals( CallToActionType::number( $data['content'] ), $asset_operation->getCreate()->getCallToActionAsset()->getCallToAction() );

	}

	public function test_create_operation_invalid_asset_field_type() {
		$data = [
			'field_type' => 'invalid',
			'content'    => CallToActionType::SHOP_NOW,
		];

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Asset Field type not supported' );

		$this->asset->create_operation( $data, self::TEMPORARY_ID );

	}


}
