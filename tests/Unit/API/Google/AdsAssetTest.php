<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CallToActionType;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Exception;
use WP_Error;
use ArrayObject;


defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsAsset                    $asset
 * @property MockObject|WP               $wp
 */
class AdsAssetTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const MAX_PAYLOAD_BYTES = 30 * 1024 * 1024;
	protected const TEMPORARY_ID      = -5;
	protected const TEST_ASSET_ID     = 6677889911;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->wp      = $this->createMock( WP::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->asset = new AdsAsset( $this->client, $this->wp );
		$this->asset->set_options_object( $this->options );
	}

	protected function assertAssetTypeConversion( $data ) {
		$row      = $this->generate_asset_row_mock( $data );
		$expected = [
			'id'      => self::TEST_ASSET_ID,
			'content' => $data['content'],
		];
		$this->assertEquals( $expected, $this->asset->convert_asset( $row ) );
	}

	protected function assertHasAssetCreateOperation( $asset_operation, $asset_id = self::TEMPORARY_ID ) {
		$this->assertTrue( $asset_operation->hasCreate() );
		$this->assertEquals( ResourceNames::forAsset( $this->options->get_ads_id(), $asset_id ), $asset_operation->getCreate()->getResourceName() );
	}

	public function test_convert_asset_text() {
		$data = [
			'field_type' => AssetFieldType::HEADLINE,
			'content'    => 'Test headline',
			'id'         => self::TEST_ASSET_ID,
		];
		$this->assertAssetTypeConversion( $data );
	}

	public function test_convert_asset_image() {
		$data = [
			'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
			'content'    => 'https://example.com/image.jpg',
			'id'         => self::TEST_ASSET_ID,
		];

		$this->assertAssetTypeConversion( $data );
	}

	public function test_convert_asset_call_action() {
		$data = [
			'field_type' => AssetFieldType::CALL_TO_ACTION_SELECTION,
			'content'    => CallToActionType::SHOP_NOW,
			'id'         => self::TEST_ASSET_ID,
		];

		$this->assertAssetTypeConversion( $data );
	}

	public function test_create_assets_text_asset() {
		$data = [
			'id'         => self::TEMPORARY_ID,
			'field_type' => AssetFieldType::HEADLINE,
			'content'    => 'Test headline',
		];

		$this->generate_asset_mutate_mock( 'create', $data );
		$this->assertEquals( $this->generate_asset_resource_name( $data['id'] ), $this->asset->create_assets( [ $data ] )[0] );
	}

	public function test_create_assets_call_to_action_asset() {
		$data = [
			'id'         => self::TEMPORARY_ID,
			'field_type' => AssetFieldType::CALL_TO_ACTION_SELECTION,
			'content'    => CallToActionType::SHOP_NOW,
		];

		$this->generate_asset_mutate_mock( 'create', $data );
		$this->assertEquals( $this->generate_asset_resource_name( $data['id'] ), $this->asset->create_assets( [ $data ] )[0] );

	}

	public function test_create_assets_image_asset() {
		$data = [
			'id'         => self::TEMPORARY_ID,
			'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
			'content'    => 'image.jpg',
		];

		$this->wp->expects( $this->exactly( 1 ) )
			->method( 'wp_remote_get' )
			->with( $data['content'] )
			->willReturn(
				[
					'body'    => $data['content'],
					'headers' => new ArrayObject( [ 'content-length' => 12345 ] ),
				]
			);

			$this->generate_asset_mutate_mock( 'create', $data );
			$this->assertEquals( $this->generate_asset_resource_name( $data['id'] ), $this->asset->create_assets( [ $data ] )[0] );

	}

	public function test_create_assets_image_asset_exception() {
		$data = [
			'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
			'content'    => 'https://incorrect_url.com/image.jpg',
		];

		$this->wp->expects( $this->exactly( 1 ) )
			->method( 'wp_remote_get' )
			->with( $data['content'] )
			->willReturn(
				new WP_Error( 'Incorrect image asset url.' )
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Incorrect image asset url.' );
		$this->asset->create_assets( [ $data ], self::TEMPORARY_ID );
	}

	public function test_create_assets_invalid_asset_field_type() {
		$data = [
			'field_type' => 'invalid',
			'content'    => CallToActionType::SHOP_NOW,
		];

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Asset Field type not supported' );

		$this->asset->create_assets( [ $data ], self::TEMPORARY_ID );

	}

	public function test_create_batches_multiple() {
		$data = [
			[
				'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
				'content'    => 'https://test.com/image1.jpg',
			],
			[
				'field_type' => AssetFieldType::MARKETING_IMAGE,
				'content'    => 'https://test.com/image2.jpg',
			],
			[
				'field_type' => AssetFieldType::MARKETING_IMAGE,
				'content'    => 'https://test.com/image3.jpg',
			],
			[
				'field_type' => AssetFieldType::MARKETING_IMAGE,
				'content'    => 'https://test.com/image4.jpg',
			],
		];

		$this->wp->expects( $this->exactly( 4 ) )
			->method( 'wp_remote_get' )
			->withConsecutive( [ $data[0]['content'] ], [ $data[1]['content'] ] )
			->willReturnOnConsecutiveCalls(
				[
					'body'    => $data[0]['content'],
					'headers' => new ArrayObject( [ 'content-length' => 25 * 1024 * 1024 ] ),
				],
				[
					'body'    => $data[1]['content'],
					'headers' => new ArrayObject( [ 'content-length' => 3 * 1024 * 1024 ] ),
				],
				[
					'body'    => $data[2]['content'],
					'headers' => new ArrayObject( [ 'content-length' => 6 * 1024 * 1024 ] ),
				],
				[
					'body'    => $data[3]['content'],
					'headers' => new ArrayObject( [ 'content-length' => 28 * 1024 * 1024 ] ),
				],
			);

		$batches = $this->asset->create_batches( $data, self::MAX_PAYLOAD_BYTES );

		$this->assertEquals( 3, count( $batches ) );

		// First Batch
		$this->assertEquals( 2, count( $batches[0] ) );
		$this->assertEquals( $data[0]['content'], $batches[0][0]['content'] );
		$this->assertEquals( $data[1]['content'], $batches[0][1]['content'] );

		// Second Batch
		$this->assertEquals( 1, count( $batches[1] ) );
		$this->assertEquals( $data[2]['content'], $batches[1][0]['content'] );

		// Third Batch
		$this->assertEquals( 1, count( $batches[1] ) );
		$this->assertEquals( $data[3]['content'], $batches[2][0]['content'] );

	}


}
