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
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidSourceImage;
use Google\Ads\GoogleAds\Util\V23\ResourceNames;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Exception;
use WP_Error;
use ArrayObject;
use Automattic\WooCommerce\GoogleListingsAndAds\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsAssetTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|WP $wp */
	protected $wp;

	/** @var AdsAsset $asset */
	protected $asset;

	protected const MAX_PAYLOAD_BYTES = 30 * 1024 * 1024;
	protected const TEMPORARY_ID      = -5;
	protected const TEST_ASSET_ID     = 6677889911;
	protected const MAX_IMAGE_BYTES   = 5 * 1024 * 1024;

	protected const IMAGE_REQUEST_ARGS = [
		'reject_unsafe_urls'  => true,
		'limit_response_size' => self::MAX_IMAGE_BYTES + 1,
		'timeout'             => 10,
		'redirection'         => 3,
	];

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

	public function test_convert_youtube_video_asset() {
		$data = [
			'field_type' => AssetFieldType::YOUTUBE_VIDEO,
			'content'    => 'dQw4w9WgXcQ',
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

	/**
	 * @group youtube
	 */
	public function test_create_assets_youtube_video_asset() {
		$data = [
			'id'         => self::TEMPORARY_ID,
			'field_type' => AssetFieldType::YOUTUBE_VIDEO,
			'content'    => 'dQw4w9WgXcQ',
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
			->with( $data['content'], self::IMAGE_REQUEST_ARGS )
			->willReturn(
				[
					'body'     => $this->get_image_body(),
					'headers'  => new ArrayObject(
						[
							'content-length' => 12345,
							'content-type'   => 'image/jpeg',
						]
					),
					'response' => [ 'code' => 200 ],
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
			->with( $data['content'], self::IMAGE_REQUEST_ARGS )
			->willReturn(
				new WP_Error( 'Incorrect image asset url.' )
			);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( sprintf( 'There was a problem loading the url: %s', $data['content'] ) );
		$this->asset->create_assets( [ $data ], self::TEMPORARY_ID );
	}

	public function test_get_image_data_non_200_response() {
		$url = 'https://example.com/missing.jpg';

		$this->wp->expects( $this->once() )
			->method( 'wp_remote_get' )
			->with( $url, self::IMAGE_REQUEST_ARGS )
			->willReturn(
				[
					'body'     => '<html>Not Found</html>',
					'headers'  => new ArrayObject( [ 'content-type' => 'image/jpeg' ] ),
					'response' => [ 'code' => 404 ],
				]
			);

		try {
			$this->asset->get_image_data( $url );
			$this->fail( 'Expected InvalidSourceImage to be thrown.' );
		} catch ( InvalidSourceImage $e ) {
			$this->assertEquals( InvalidSourceImage::REASON_FETCH_FAILED, $e->get_reason() );
			$this->assertEquals( sprintf( 'There was a problem loading the url: %s', $url ), $e->getMessage() );
		}
	}

	public function test_get_image_data_non_image_content_type() {
		$url = 'https://example.com/error-page';

		$this->wp->expects( $this->once() )
			->method( 'wp_remote_get' )
			->with( $url, self::IMAGE_REQUEST_ARGS )
			->willReturn(
				[
					'body'     => '<html>Error</html>',
					'headers'  => new ArrayObject( [ 'content-type' => 'text/html; charset=UTF-8' ] ),
					'response' => [ 'code' => 200 ],
				]
			);

		try {
			$this->asset->get_image_data( $url );
			$this->fail( 'Expected InvalidSourceImage to be thrown.' );
		} catch ( InvalidSourceImage $e ) {
			$this->assertEquals( InvalidSourceImage::REASON_FETCH_FAILED, $e->get_reason() );
		}
	}

	public function test_get_image_data_returns_received_size() {
		$url  = 'https://example.com/image.gif';
		$body = $this->get_image_body( 2048 );

		$this->wp->expects( $this->once() )
			->method( 'wp_remote_get' )
			->with( $url, self::IMAGE_REQUEST_ARGS )
			->willReturn( $this->get_image_response( $body, [ 'content-length' => 10 ] ) );

		$this->assertEquals(
			[
				'body' => $body,
				'size' => 2048,
			],
			$this->asset->get_image_data( $url )
		);
	}

	public function test_get_image_data_too_large_content_length() {
		$this->wp->method( 'wp_remote_get' )
			->willReturn( $this->get_image_response( $this->get_image_body(), [ 'content-length' => self::MAX_IMAGE_BYTES + 1 ] ) );

		$this->assert_image_data_throws( InvalidSourceImage::REASON_TOO_LARGE );
	}

	public function test_get_image_data_too_large_without_content_length() {
		$this->wp->method( 'wp_remote_get' )
			->willReturn( $this->get_image_response( $this->get_image_body( self::MAX_IMAGE_BYTES + 1 ) ) );

		$this->assert_image_data_throws( InvalidSourceImage::REASON_TOO_LARGE );
	}

	public function test_get_image_data_too_large_with_understated_content_length() {
		$this->wp->method( 'wp_remote_get' )
			->willReturn( $this->get_image_response( $this->get_image_body( self::MAX_IMAGE_BYTES + 1 ), [ 'content-length' => 1024 ] ) );

		$this->assert_image_data_throws( InvalidSourceImage::REASON_TOO_LARGE );
	}

	public function test_get_image_data_non_image_body() {
		$this->wp->method( 'wp_remote_get' )
			->willReturn( $this->get_image_response( '<html>Not an image</html>' ) );

		$this->assert_image_data_throws( InvalidSourceImage::REASON_FETCH_FAILED );
	}

	public function test_get_image_data_unsupported_image_type() {
		// A BMP header, which Google Ads doesn't accept as an image asset.
		$this->wp->method( 'wp_remote_get' )
			->willReturn( $this->get_image_response( base64_decode( 'Qk06AAAAAAAAADYAAAAoAAAAAQAAAAEAAAABABgAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAA////AA==' ) ) );

		$this->assert_image_data_throws( InvalidSourceImage::REASON_FETCH_FAILED );
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
			// First Batch
			if ( $matcher->getInvocationCount() === 1 ) {
				$this->assertEquals( 2, count( $operations ) );
				// Second Batch
			} elseif ( $matcher->getInvocationCount() === 2 ) {
				$index = 2;
				$this->assertEquals( 1, count( $operations ) );
				// Third Batch
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

	public function test_create_operations_returns_expected_operations() {
		$assets = [
			[
				'field_type' => AssetFieldType::HEADLINE,
				'content'    => 'Test headline',
			],
			[
				'field_type' => AssetFieldType::SQUARE_MARKETING_IMAGE,
				'content'    => 'https://example.com/image.jpg',
			],
		];

		$this->wp->expects( $this->once() )
			->method( 'wp_remote_get' )
			->with( 'https://example.com/image.jpg', self::IMAGE_REQUEST_ARGS )
			->willReturn(
				[
					'body'     => $this->get_image_body(),
					'headers'  => new ArrayObject(
						[
							'content-length' => 1234,
							'content-type'   => 'image/jpeg',
						]
					),
					'response' => [ 'code' => 200 ],
				]
			);

		$operations = $this->asset->create_operations( $assets );

		$this->assertCount( 2, $operations );
		$this->assertTrue( $operations[0]->hasAssetOperation() );
		$this->assertTrue( $operations[1]->hasAssetOperation() );
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
			++$starting_index;
		}
	}

	/**
	 * Asserts get_image_data() throws InvalidSourceImage with the given reason.
	 *
	 * @param string $reason The expected InvalidSourceImage reason.
	 */
	protected function assert_image_data_throws( string $reason ) {
		try {
			$this->asset->get_image_data( 'https://example.com/image.gif' );
			$this->fail( 'Expected InvalidSourceImage to be thrown.' );
		} catch ( InvalidSourceImage $e ) {
			$this->assertEquals( $reason, $e->get_reason() );
		}
	}

	/**
	 * Returns a GIF image body padded to the given size.
	 *
	 * @param int $size The body size in bytes. Defaults to the bare GIF.
	 *
	 * @return string The image body.
	 */
	protected function get_image_body( int $size = 0 ): string {
		$gif = base64_decode( 'R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==' );

		return str_pad( $gif, $size, "\0" );
	}

	/**
	 * Returns a successful wp_remote_get response for an image.
	 *
	 * @param string $body    The response body.
	 * @param array  $headers Extra response headers.
	 *
	 * @return array The response.
	 */
	protected function get_image_response( string $body, array $headers = [] ): array {
		return [
			'body'     => $body,
			'headers'  => new ArrayObject( $headers + [ 'content-type' => 'image/gif' ] ),
			'response' => [ 'code' => 200 ],
		];
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
				$carry[] = [ $item['content'], self::IMAGE_REQUEST_ARGS ];
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
				'body'     => $this->get_image_body( $size * 1024 * 1024 ),
				'headers'  => new ArrayObject(
					[
						'content-length' => $size * 1024 * 1024,
						'content-type'   => 'image/jpeg',
					]
				),
				'response' => [ 'code' => 200 ],
			];

			++$index;
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
