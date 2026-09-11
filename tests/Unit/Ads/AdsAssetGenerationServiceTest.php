<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAssetGenerationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidSourceImage;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\V23\Services\GeneratedImage;
use Google\Ads\GoogleAds\V23\Services\GenerateImagesResponse;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGenerationServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads
 */
class AdsAssetGenerationServiceTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var AdsAssetGenerationService $service */
	protected $service;

	/** @var MockObject|AdsAsset $ads_asset */
	protected $ads_asset;

	protected const TEST_ADS_ID   = 1234567890;
	protected const TEST_SITE_URL = 'https://example.com';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options   = $this->createMock( OptionsInterface::class );
		$this->ads_asset = $this->createMock( AdsAsset::class );
		$this->service   = new AdsAssetGenerationService( $this->client, $this->ads_asset );
		$this->service->set_options_object( $this->options );

		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
	}

	public function test_generate_text_with_defaults() {
		$expected_text_assets = [
			[
				'text' => 'Generated headline text example.',
				'type' => 'headline',
			],
			[
				'text' => 'Generated long headline text example.',
				'type' => 'long_headline',
			],
			[
				'text' => 'Generated description text example.',
				'type' => 'description',
			],
		];

		$this->generate_text_assets_mock( $expected_text_assets );

		$result = $this->service->generate_text(
			[
				'final_url'         => self::TEST_SITE_URL,
				'asset_field_types' => [ 'headline', 'long_headline', 'description' ],
			]
		);

		$this->assertEquals( $expected_text_assets, $result );
	}

	public function test_generate_text_with_custom_final_url() {
		$final_url            = 'https://custom-url.com';
		$expected_text_assets = [
			[
				'text' => 'Custom headline',
				'type' => 'headline',
			],
		];

		$this->generate_text_assets_mock( $expected_text_assets );

		$result = $this->service->generate_text(
			[
				'final_url'         => $final_url,
				'asset_field_types' => [
					'headline',
					'long_headline',
					'description',
				],
			]
		);

		$this->assertEquals( $expected_text_assets, $result );
	}

	public function test_generate_text_with_specific_types() {
		$expected_text_assets = [
			[
				'text' => 'Headline only',
				'type' => 'headline',
			],
		];

		$this->generate_text_assets_mock( $expected_text_assets );

		$result = $this->service->generate_text( [ 'asset_field_types' => [ 'headline' ] ] );

		$this->assertEquals( $expected_text_assets, $result );
	}

	public function test_generate_text_exception() {
		$this->generate_text_assets_mock_exception(
			new ApiException( 'API error', 7 )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to generate text assets' );

		$this->service->generate_text(
			[
				'asset_field_types' => [ 'headline' ],
			]
		);
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_client_exception' ) );
	}

	public function test_generate_text_no_ads_id() {
		// Create a new options mock that returns 0 for get_ads_id
		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get_ads_id' )->willReturn( 0 );
		$this->service->set_options_object( $options );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ads account ID is required' );

		$this->service->generate_text( [] );
	}

	public function test_generate_text_uses_defaults_when_no_types_provided() {
		$expected_text_assets = [
			[
				'text' => 'Default headline',
				'type' => 'headline',
			],
			[
				'text' => 'Default long headline',
				'type' => 'long_headline',
			],
			[
				'text' => 'Default description',
				'type' => 'description',
			],
		];

		$this->generate_text_assets_mock( $expected_text_assets );

		$result = $this->service->generate_text( [ 'final_url' => 'https://example.com' ] );

		$this->assertEquals( $expected_text_assets, $result );
	}

	public function test_generate_images_with_defaults() {
		$expected_image_assets = [
			[
				'temporary_image_url' => 'https://example.com/temporary_image_url-marketing.jpg',
				'type'                => 'marketing_image',
			],
			[
				'temporary_image_url' => 'https://example.com/temporary_image_url-square.jpg',
				'type'                => 'square_marketing_image',
			],
			[
				'temporary_image_url' => 'https://example.com/temporary_image_url-portrait.jpg',
				'type'                => 'portrait_marketing_image',
			],
		];

		$this->generate_image_assets_mock( $expected_image_assets );

		$result = $this->service->generate_images( [] );

		$this->assertEquals( $expected_image_assets, $result );
	}

	public function test_generate_images_with_custom_final_url() {
		$final_url             = 'https://custom-url.com';
		$expected_image_assets = [
			[
				'temporary_image_url' => 'https://example.com/custom-image.jpg',
				'type'                => 'marketing_image',
			],
		];

		$this->generate_image_assets_mock( $expected_image_assets );

		$result = $this->service->generate_images( [ 'final_url' => $final_url ] );

		$this->assertEquals( $expected_image_assets, $result );
	}

	public function test_generate_images_with_specific_types() {
		$expected_image_assets = [
			[
				'temporary_image_url' => 'https://example.com/marketing-image.jpg',
				'type'                => 'marketing_image',
			],
		];

		$this->generate_image_assets_mock( $expected_image_assets );

		$result = $this->service->generate_images( [ 'asset_field_types' => [ 'marketing_image' ] ] );

		$this->assertEquals( $expected_image_assets, $result );
	}

	public function test_generate_images_with_prompt_uses_freeform_generation() {
		$prompt = 'A red bicycle on a white background';

		$image_asset = $this->createMock( GeneratedImage::class );
		$image_asset->method( 'getImageTemporaryUrl' )->willReturn( 'https://example.com/freeform.jpg' );
		$image_asset->method( 'getAssetFieldType' )->willReturn( AssetFieldType::number( 'marketing_image' ) );

		$response = $this->createMock( GenerateImagesResponse::class );
		$response->method( 'getGeneratedImages' )->willReturn( [ $image_asset ] );

		$this->asset_generation_service
			->expects( $this->once() )
			->method( 'generateImages' )
			->with(
				$this->callback(
					function ( $request ) use ( $prompt ) {
						return 'freeform_generation' === $request->getGenerationType()
							&& $prompt === $request->getFreeformGeneration()->getFreeformPrompt();
					}
				)
			)
			->willReturn( $response );

		$result = $this->service->generate_images( [ 'prompt' => $prompt ] );

		$this->assertEquals(
			[
				[
					'temporary_image_url' => 'https://example.com/freeform.jpg',
					'type'                => 'marketing_image',
				],
			],
			$result
		);
	}

	public function test_generate_images_without_prompt_uses_final_url_generation() {
		$response = $this->createMock( GenerateImagesResponse::class );
		$response->method( 'getGeneratedImages' )->willReturn( [] );

		$this->asset_generation_service
			->expects( $this->once() )
			->method( 'generateImages' )
			->with(
				$this->callback(
					function ( $request ) {
						return 'final_url_generation' === $request->getGenerationType();
					}
				)
			)
			->willReturn( $response );

		$this->service->generate_images( [] );
	}

	public function test_generate_images_with_source_image_url_uses_recontext_generation() {
		$source_image_url = 'https://example.com/source.jpg';
		$prompt           = 'Place the product on a beach';

		$this->ads_asset->expects( $this->once() )
			->method( 'get_image_data' )
			->with( $source_image_url )
			->willReturn(
				[
					'body' => 'raw-image-bytes',
					'size' => 12345,
				]
			);

		$image_asset = $this->createMock( GeneratedImage::class );
		$image_asset->method( 'getImageTemporaryUrl' )->willReturn( 'https://example.com/recontext.jpg' );
		$image_asset->method( 'getAssetFieldType' )->willReturn( AssetFieldType::number( 'marketing_image' ) );

		$response = $this->createMock( GenerateImagesResponse::class );
		$response->method( 'getGeneratedImages' )->willReturn( [ $image_asset ] );

		$this->asset_generation_service
			->expects( $this->once() )
			->method( 'generateImages' )
			->with(
				$this->callback(
					function ( $request ) use ( $prompt ) {
						if ( 'product_recontext_generation' !== $request->getGenerationType() ) {
							return false;
						}

						$recontext = $request->getProductRecontextGeneration();
						$images    = iterator_to_array( $recontext->getSourceImages() );

						return $prompt === $recontext->getPrompt()
							&& 1 === count( $images )
							&& 'raw-image-bytes' === $images[0]->getImageData();
					}
				)
			)
			->willReturn( $response );

		$result = $this->service->generate_images(
			[
				'source_image_url' => $source_image_url,
				'prompt'           => $prompt,
			]
		);

		$this->assertEquals(
			[
				[
					'temporary_image_url' => 'https://example.com/recontext.jpg',
					'type'                => 'marketing_image',
				],
			],
			$result
		);
	}

	public function test_generate_images_source_image_url_takes_precedence_over_prompt() {
		$this->ads_asset->method( 'get_image_data' )->willReturn(
			[
				'body' => 'raw-image-bytes',
				'size' => 1,
			]
		);

		$response = $this->createMock( GenerateImagesResponse::class );
		$response->method( 'getGeneratedImages' )->willReturn( [] );

		$this->asset_generation_service
			->expects( $this->once() )
			->method( 'generateImages' )
			->with(
				$this->callback(
					function ( $request ) {
						return 'product_recontext_generation' === $request->getGenerationType();
					}
				)
			)
			->willReturn( $response );

		$this->service->generate_images(
			[
				'source_image_url' => 'https://example.com/source.jpg',
				'prompt'           => 'A freeform prompt that should be ignored as the top-level branch',
			]
		);
	}

	public function test_generate_images_source_image_fetch_failure() {
		$this->ads_asset->method( 'get_image_data' )
			->willThrowException( InvalidSourceImage::fetch_failed( 'https://example.com/source.jpg' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Could not fetch the source image.' );

		$this->service->generate_images( [ 'source_image_url' => 'https://example.com/source.jpg' ] );
	}

	public function test_generate_images_source_image_too_large() {
		$this->ads_asset->method( 'get_image_data' )
			->willThrowException( InvalidSourceImage::too_large() );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Source image exceeds the maximum allowed size.' );

		$this->service->generate_images( [ 'source_image_url' => 'https://example.com/source.jpg' ] );
	}

	public function test_generate_images_exception() {
		$this->generate_image_assets_mock_exception(
			new ApiException( 'API error', 7 )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to generate image assets' );

		$this->service->generate_images( [] );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_client_exception' ) );
	}

	public function test_generate_images_no_ads_id() {
		// Create a new options mock that returns 0 for get_ads_id
		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get_ads_id' )->willReturn( 0 );
		$this->service->set_options_object( $options );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ads account ID is required' );

		$this->service->generate_images( [] );
	}

	public function test_generate_images_uses_default_types_when_none_provided() {
		$expected_enum_types = array_map(
			[ AssetFieldType::class, 'number' ],
			AdsAssetGenerationService::VALID_IMAGE_TYPES
		);

		$image_asset = $this->createMock( GeneratedImage::class );
		$image_asset->method( 'getImageTemporaryUrl' )->willReturn( 'https://example.com/image.jpg' );
		$image_asset->method( 'getAssetFieldType' )->willReturn( AssetFieldType::number( 'marketing_image' ) );

		$response = $this->createMock( GenerateImagesResponse::class );
		$response->method( 'getGeneratedImages' )->willReturn( [ $image_asset ] );

		$this->asset_generation_service
			->expects( $this->once() )
			->method( 'generateImages' )
			->with(
				$this->callback(
					function ( $request ) use ( $expected_enum_types ) {
						$actual = iterator_to_array( $request->getAssetFieldTypes() );
						sort( $actual );
						sort( $expected_enum_types );
						return $actual === $expected_enum_types;
					}
				)
			)
			->willReturn( $response );

		$this->service->generate_images( [] );
	}

	/**
	 * Regression: constructing the service must not eagerly build the
	 * Google Ads V23 Asset Generation service client. The container resolves
	 * this class during `rest_api_init` (via REST controller tags), which
	 * happens before V23 service clients are guaranteed to be loadable —
	 * eager construction in 3.7.x produced a fatal on admin page loads after
	 * a plugin update.
	 */
	public function test_constructor_does_not_resolve_service_client() {
		$ads_client = $this->createMock( GoogleAdsClient::class );
		$ads_client->expects( $this->never() )->method( 'getAssetGenerationServiceClient' );

		new AdsAssetGenerationService( $ads_client, $this->ads_asset );
	}

	/**
	 * Regression: even when the underlying V23 service client factory
	 * throws, constructing the service must succeed — the factory call is
	 * deferred to the first generate_* call site.
	 */
	public function test_constructor_succeeds_when_service_client_factory_throws() {
		$ads_client = $this->createMock( GoogleAdsClient::class );
		$ads_client->method( 'getAssetGenerationServiceClient' )
			->willThrowException( new RuntimeException( 'V23 Service Clients are not fully loaded.' ) );

		new AdsAssetGenerationService( $ads_client, $this->ads_asset );

		$this->assertTrue( true, 'Constructor must not invoke the V23 service client factory.' );
	}
}
