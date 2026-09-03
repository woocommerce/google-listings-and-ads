<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAssetGenerationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AssetGenerationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;

/**
 * Class AssetGenerationControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class AssetGenerationControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|AdsAssetGenerationService $service */
	protected $service;

	/** @var AssetGenerationController $controller */
	protected $controller;

	protected const ROUTE_GENERATE_TEXT   = '/wc/gla/ads/assets/generate-text';
	protected const ROUTE_GENERATE_IMAGES = '/wc/gla/ads/assets/generate-images';
	protected const TEST_SITE_URL         = 'https://example.com';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock the site URL filter to return TEST_SITE_URL.
		add_filter(
			'woocommerce_gla_site_url',
			function () {
				return self::TEST_SITE_URL;
			}
		);

		$this->service    = $this->createMock( AdsAssetGenerationService::class );
		$this->controller = new AssetGenerationController( $this->server, $this->service );
		$this->controller->register();
	}

	public function test_generate_text_with_defaults() {
		// Service expects empty array when no types provided (service handles defaults).
		$this->service->expects( $this->once() )
			->method( 'generate_text' )
			->with(
				[
					'final_url'         => self::TEST_SITE_URL,
					'asset_field_types' => [],
				]
			)
			->willReturn(
				[
					[
						'text' => 'Test headline',
						'type' => 'headline',
					],
					[
						'text' => 'Test long headline',
						'type' => 'long_headline',
					],
					[
						'text' => 'Test description',
						'type' => 'description',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_TEXT, 'POST' );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::TEST_SITE_URL, $data['final_url'] );
		$this->assertCount( 3, $data['items'] );
		// Verify types are lowercase in response.
		$this->assertEquals( 'headline', $data['items'][0]['type'] );
		$this->assertEquals( 'long_headline', $data['items'][1]['type'] );
		$this->assertEquals( 'description', $data['items'][2]['type'] );
		$this->assertEquals( 'Test headline', $data['items'][0]['text'] );
	}

	public function test_generate_text_with_custom_url() {
		$params = [
			'final_url' => 'https://custom-url.com',
		];

		$this->service->expects( $this->once() )
			->method( 'generate_text' )
			->with(
				[
					'final_url'         => 'https://custom-url.com',
					'asset_field_types' => [],
				]
			)
			->willReturn(
				[
					[
						'text' => 'Custom headline',
						'type' => 'headline',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_TEXT, 'POST', $params );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'https://custom-url.com', $data['final_url'] );
		$this->assertEquals( 'headline', $data['items'][0]['type'] );
	}

	public function test_generate_text_with_specific_types() {
		$params = [
			'types' => [ 'headline' ],
		];

		$this->service->expects( $this->once() )
			->method( 'generate_text' )
			->with(
				[
					'final_url'         => self::TEST_SITE_URL,
					'asset_field_types' => [ 'headline' ],
				]
			)
			->willReturn(
				[
					[
						'text' => 'Headline only',
						'type' => 'headline',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_TEXT, 'POST', $params );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $data['items'] );
		$this->assertEquals( 'headline', $data['items'][0]['type'] );
	}

	public function test_generate_text_type_conversion() {
		$params = [
			'types' => [ 'headline', 'description' ],
		];

		// Verify lowercase input is converted to uppercase for service.
		$this->service->expects( $this->once() )
			->method( 'generate_text' )
			->with(
				$this->callback(
					function ( $args ) {
						return $args['asset_field_types'] === [ 'headline', 'description' ];
					}
				)
			)
			->willReturn(
				[
					[
						'text' => 'Test',
						'type' => 'headline',
					],
					[
						'text' => 'Test',
						'type' => 'description',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_TEXT, 'POST', $params );

		// Verify uppercase response is converted to lowercase.
		$data = $response->get_data();
		$this->assertEquals( 'headline', $data['items'][0]['type'] );
		$this->assertEquals( 'description', $data['items'][1]['type'] );
	}

	public function test_generate_text_exception() {
		$this->service
			->method( 'generate_text' )
			->willThrowException( new Exception( 'Service error', 500 ) );

		$response = $this->do_request( self::ROUTE_GENERATE_TEXT, 'POST' );

		$this->assertEquals( 'Service error', $response->get_data()['message'] );
		$this->assertEquals( 500, $response->get_status() );
	}

	public function test_generate_images_with_defaults() {
		// Service expects empty array for types (API generates all).
		$this->service->expects( $this->once() )
			->method( 'generate_images' )
			->with(
				[
					'final_url'        => self::TEST_SITE_URL,
					'prompt'           => '',
					'source_image_url' => '',
				]
			)
			->willReturn(
				[
					[
						'temporary_image_url' => 'https://example.com/image-marketing.jpg',
						'type'                => 'marketing_image',
					],
					[
						'temporary_image_url' => 'https://example.com/image-square.jpg',
						'type'                => 'square_marketing_image',
					],
					[
						'temporary_image_url' => 'https://example.com/image-portrait.jpg',
						'type'                => 'portrait_marketing_image',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST' );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::TEST_SITE_URL, $data['final_url'] );
		$this->assertCount( 3, $data['items'] );
		// Verify types are lowercase in response.
		$this->assertEquals( 'marketing_image', $data['items'][0]['type'] );
		$this->assertEquals( 'square_marketing_image', $data['items'][1]['type'] );
		$this->assertEquals( 'portrait_marketing_image', $data['items'][2]['type'] );
	}

	public function test_generate_images_with_custom_url() {
		$params = [
			'final_url' => 'https://custom-url.com',
		];

		$this->service->expects( $this->once() )
			->method( 'generate_images' )
			->with(
				[
					'final_url'        => 'https://custom-url.com',
					'prompt'           => '',
					'source_image_url' => '',
				]
			)
			->willReturn(
				[
					[
						'temporary_image_url' => 'https://example.com/custom-image.jpg',
						'type'                => 'marketing_image',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'https://custom-url.com', $data['final_url'] );
		$this->assertEquals( 'marketing_image', $data['items'][0]['type'] );
	}

	public function test_generate_images_with_prompt() {
		$params = [
			'prompt' => 'A red bicycle on a white background',
		];

		$this->service->expects( $this->once() )
			->method( 'generate_images' )
			->with(
				[
					'final_url'        => self::TEST_SITE_URL,
					'prompt'           => 'A red bicycle on a white background',
					'source_image_url' => '',
				]
			)
			->willReturn(
				[
					[
						'temporary_image_url' => 'https://example.com/freeform-image.jpg',
						'type'                => 'marketing_image',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'marketing_image', $data['items'][0]['type'] );
	}

	public function test_generate_images_with_source_image_url() {
		$params = [
			'source_image_url' => 'https://example.com/source.jpg',
		];

		$this->service->expects( $this->once() )
			->method( 'generate_images' )
			->with(
				[
					'final_url'        => self::TEST_SITE_URL,
					'prompt'           => '',
					'source_image_url' => 'https://example.com/source.jpg',
				]
			)
			->willReturn(
				[
					[
						'temporary_image_url' => 'https://example.com/recontext-image.jpg',
						'type'                => 'marketing_image',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'marketing_image', $data['items'][0]['type'] );
	}

	public function test_generate_images_prompt_too_long() {
		$this->service->expects( $this->never() )->method( 'generate_images' );

		$params = [
			'prompt' => str_repeat( 'a', 1501 ),
		];

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_gla_prompt_too_long', $response->get_data()['code'] );
		$this->assertEquals( 'Prompt must be 1500 characters or fewer.', $response->get_data()['message'] );
	}

	public function test_generate_images_source_image_fetch_failure() {
		$this->service
			->method( 'generate_images' )
			->willThrowException( new Exception( 'Could not fetch the source image.' ) );

		$params = [
			'source_image_url' => 'https://example.com/source.jpg',
		];

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Could not fetch the source image.', $response->get_data()['message'] );
	}

	public function test_generate_images_source_image_too_large() {
		$this->service
			->method( 'generate_images' )
			->willThrowException( new Exception( 'Source image exceeds the maximum allowed size.' ) );

		$params = [
			'source_image_url' => 'https://example.com/source.jpg',
		];

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Source image exceeds the maximum allowed size.', $response->get_data()['message'] );
	}

	public function test_generate_images_with_specific_types() {
		$params = [
			'types' => [ 'marketing_image' ],
		];

		// Service expects uppercase types.
		$this->service->expects( $this->once() )
			->method( 'generate_images' )
			->with(
				[
					'final_url'         => self::TEST_SITE_URL,
					'prompt'            => '',
					'source_image_url'  => '',
					'asset_field_types' => [ 'marketing_image' ],
				]
			)
			->willReturn(
				[
					[
						'temporary_image_url' => 'https://example.com/image.jpg',
						'type'                => 'marketing_image',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		// Verify type is lowercase in response.
		$this->assertEquals( 'marketing_image', $data['items'][0]['type'] );
	}

	public function test_generate_images_type_conversion() {
		$params = [
			'types' => [ 'marketing_image', 'square_marketing_image' ],
		];

		// Verify lowercase input is converted to uppercase for service.
		$this->service->expects( $this->once() )
			->method( 'generate_images' )
			->with(
				$this->callback(
					function ( $args ) {
						return $args['asset_field_types'] === [ 'marketing_image', 'square_marketing_image' ];
					}
				)
			)
			->willReturn(
				[
					[
						'temporary_image_url' => 'https://example.com/image1.jpg',
						'type'                => 'marketing_image',
					],
					[
						'temporary_image_url' => 'https://example.com/image2.jpg',
						'type'                => 'square_marketing_image',
					],
				]
			);

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST', $params );

		// Verify uppercase response is converted to lowercase.
		$data = $response->get_data();
		$this->assertEquals( 'marketing_image', $data['items'][0]['type'] );
		$this->assertEquals( 'square_marketing_image', $data['items'][1]['type'] );
	}

	public function test_generate_images_exception() {
		$this->service
			->method( 'generate_images' )
			->willThrowException( new Exception( 'Service error', 500 ) );

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST' );

		$this->assertEquals( 'Service error', $response->get_data()['message'] );
		$this->assertEquals( 500, $response->get_status() );
	}

	public function test_generate_text_without_permission() {
		// Remove admin capabilities.
		wp_set_current_user( 0 );

		$response = $this->do_request( self::ROUTE_GENERATE_TEXT, 'POST' );

		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_generate_images_without_permission() {
		// Remove admin capabilities.
		wp_set_current_user( 0 );

		$response = $this->do_request( self::ROUTE_GENERATE_IMAGES, 'POST' );

		$this->assertEquals( 401, $response->get_status() );
	}
}
