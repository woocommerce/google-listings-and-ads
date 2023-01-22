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

	public function test_create_one_batch() {
		// Create asset with different sizes in MB
		$data   = $this->get_data_batches( [ 3, 3, 4, 5 ] );
		$assets = $data['assets'];

		$this->wp->expects( $this->exactly( count( $assets ) ) )
			->method( 'wp_remote_get' )
			->withConsecutive( ...$this->get_wp_remote_get_params( $assets ) )
			->willReturnOnConsecutiveCalls(
				...$data['wp_remote_responses']
			);

		$assert_batches = function ( $matcher, $operations ) use ( $assets ) {
			$index = 0;
			if ( $matcher->getInvocationCount() === 1 ) {
				$this->assertEquals( 4, count( $operations ) );
			} else {
				throw new Exception( 'Unexpected number of batches' );
			}

			$this->assert_asset_content( $operations, $assets, $index );

		};

		$matcher = $this->exactly( 1 );
		$this->generate_asset_batch_mutate_mock( $matcher, $assert_batches );
		$this->asset->create_assets( $assets );

	}

	public function test_create_batches_multiple() {
		// Create asset with different sizes in MB
		$data   = $this->get_data_batches( [ 3, 1, 4, 4 ] );
		$assets = $data['assets'];

		$this->wp->expects( $this->exactly( count( $assets ) ) )
			->method( 'wp_remote_get' )
			->withConsecutive( ...$this->get_wp_remote_get_params( $assets ) )
			->willReturnOnConsecutiveCalls(
				...$data['wp_remote_responses']
			);

		$assert_batches = function ( $matcher, $operations ) use ( $assets ) {
			$index = 0;
			if ( $matcher->getInvocationCount() === 1 ) {
				$this->assertEquals( 2, count( $operations ) );
			} elseif ( $matcher->getInvocationCount() === 2 ) {
				$index = 2;
				$this->assertEquals( 1, count( $operations ) );
			} elseif ( $matcher->getInvocationCount() === 3 ) {
				$index = 3;
				$this->assertEquals( 1, count( $operations ) );
			} else {
				throw new Exception( 'Unexpected number of batches' );
			}

			$this->assert_asset_content( $operations, $assets, $index );

		};

		$matcher = $this->exactly( 3 );
		$this->generate_asset_batch_mutate_mock( $matcher, $assert_batches );
		$this->asset->create_assets( $assets, 5 * 1024 * 1024 );

	}

	/**
	 * Asserts the content of the asset.
	 *
	 * @param array $operations The list of operations.
	 * @param array $assets The list of assets.
	 * @param int   $starting_index The starting index.
	 */
	protected function assert_asset_content( $operations, $assets, $starting_index = 0 ) {
		foreach ( $operations as $operation ) {
			$this->assertEquals( $assets[ $starting_index ]['content'], $this->get_asset_content( $operation->getAssetOperation()->getCreate(), $assets[ $starting_index ]['field_type'] ) );
			$starting_index++;
		}
	}

	/**
	 * Returns expected params for wp_remote_get.
	 *
	 * @param array $assets The list of assets.
	 *
	 * @return array The list of params.
	 */
	protected function get_wp_remote_get_params( array $assets ) {
		return array_reduce(
			$assets,
			function ( $carry, $item ) {
				$carry[] = [ 'content' => $item['content'] ];
				return $carry;
			},
			[]
		);
	}

	/**
	 * Returns a list of test assets.
	 *
	 * @param array $sizes The sizes of the responses.
	 *
	 * @return array The list of test assets.
	 */
	protected function get_data_batches( array $sizes ): array {
		$assets = $this->get_test_assets( count( $sizes ) );

		return [
			'wp_remote_responses' => $this->get_wp_remote_responses( $sizes, $assets ),
			'assets'              => $assets,
		];
	}


	/**
	 * Returns a list of wp_remote responses.
	 *
	 * @param array $sizes The sizes of the responses.
	 * @param array $data  The data to use for the responses.
	 *
	 * @return array The list of responses.
	 */
	protected function get_wp_remote_responses( array $sizes, array $data ): array {
		$responses = [];
		$index     = 0;

		foreach ( $sizes as $size ) {
			$responses[] = [
				'body'    => $data[ $index ]['content'],
				'headers' => new ArrayObject( [ 'content-length' => $size * 1024 * 1024 ] ),
			];

			$index++;
		}

		return $responses;

	}


	/**
	 * Returns a list of test assets.
	 *
	 * @param int $qty The number of assets to return.
	 *
	 * @return array The list of assets.
	 */
	protected function get_test_assets( int $qty = 4 ): array {
		$assets = [];

		for ( $i = 0; $i < $qty; $i++ ) {
			$assets[] = [
				'id'         => null,
				'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
				'content'    => 'image' . $i . '.jpg',
			];
		}

		return $assets;
	}




}
