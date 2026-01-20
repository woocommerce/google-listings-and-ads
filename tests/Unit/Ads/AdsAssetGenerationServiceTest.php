<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAssetGenerationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;

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

	protected const TEST_ADS_ID   = 1234567890;
	protected const TEST_SITE_URL = 'https://example.com';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->service = new AdsAssetGenerationService( $this->client );
		$this->service->set_options_object( $this->options );

		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
	}

	public function test_generate_text_with_defaults() {
		$expected_text_assets = [
			[
				'text' => 'Generated headline text example.',
				'type' => 'HEADLINE',
			],
			[
				'text' => 'Generated long headline text example.',
				'type' => 'LONG_HEADLINE',
			],
			[
				'text' => 'Generated description text example.',
				'type' => 'DESCRIPTION',
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
				'type' => 'HEADLINE',
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
				'type' => 'HEADLINE',
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
		$this->options->method( 'get_ads_id' )->willReturn( null );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ads account ID is required' );

		$this->service->generate_text( [] );
	}

	public function test_generate_text_no_types_provided() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Asset field types are required for text generation' );

		$this->service->generate_text( [ 'final_url' => 'https://example.com' ] );
	}

	public function test_generate_images_with_defaults() {
		$expected_image_assets = [
			[
				'temporary_image_url' => 'https://example.com/temporary_image_url-marketing.jpg',
				'type'                => 'MARKETING_IMAGE',
			],
			[
				'temporary_image_url' => 'https://example.com/temporary_image_url-square.jpg',
				'type'                => 'SQUARE_MARKETING_IMAGE',
			],
			[
				'temporary_image_url' => 'https://example.com/temporary_image_url-portrait.jpg',
				'type'                => 'PORTRAIT_MARKETING_IMAGE',
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
				'type'                => 'MARKETING_IMAGE',
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
				'type'                => 'MARKETING_IMAGE',
			],
		];

		$this->generate_image_assets_mock( $expected_image_assets );

		$result = $this->service->generate_images( [ 'asset_field_types' => [ 'marketing_image' ] ] );

		$this->assertEquals( $expected_image_assets, $result );
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
		$this->options->method( 'get_ads_id' )->willReturn( null );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ads account ID is required' );

		$this->service->generate_images( [] );
	}
}
