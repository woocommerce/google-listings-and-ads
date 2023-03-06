<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AssetSuggestionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;

/**
 * Class AssetSuggestionsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property RESTServer                         $rest_server
 * @property AssetSuggestionsService|MockObject $assets_suggestions
 */
class AssetSuggestionsControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_FINAL_URL_SUGGESTIONS = '/wc/gla/assets/final-url/suggestions';
	protected const ROUTE_ASSETS_SUGGESTIONS    = '/wc/gla/assets/suggestions';
	protected const TEST_FINAL_URL_SUGGESTIONS  = [
		[
			'id'    => 1,
			'type'  => 'post',
			'title' => 'Beanie with Logo',
			'url'   => 'http://localhost:8082/shop/clothing/accessories/beanie-with-logo/',
		],
		[
			'id'    => 2,
			'type'  => 'term',
			'title' => 'Clothing',
			'url'   => 'http://localhost:8082/product-category/clothing/',
		],

	];
	protected const TEST_NO_FINAL_URL_SUGGESTIONS = [];
	protected const TEST_ASSETS_SUGGESTIONS       = [
		'headline'                => [ 'Headline 1' ],
		'long_headline'           => [ 'Long Headline 1' ],
		'description'             => [ 'Description 1' ],
		'square_marketing_images' => [ 'https://test.com/image.png' ],
		'final_url'               => 'https://test.com/shop',
		'business_name'           => 'Test Blog',
	];

	public function setUp(): void {
		parent::setUp();

		$this->assets_suggestions = $this->createMock( AssetSuggestionsService::class );
		$this->controller         = new AssetSuggestionsController( $this->server, $this->assets_suggestions );
		$this->controller->register();
	}

	public function test_get_final_url_suggestions() {
		$this->assets_suggestions->expects( $this->once() )
			->method( 'get_final_url_suggestions' )
			->willReturn( self::TEST_FINAL_URL_SUGGESTIONS );

		$response = $this->do_request( self::ROUTE_FINAL_URL_SUGGESTIONS, 'GET' );

		$this->assertEquals( self::TEST_FINAL_URL_SUGGESTIONS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_final_url_suggestions_empty_set() {
		$this->assets_suggestions->expects( $this->once() )
			->method( 'get_final_url_suggestions' )
			->willReturn( self::TEST_NO_FINAL_URL_SUGGESTIONS );

		$response = $this->do_request( self::ROUTE_FINAL_URL_SUGGESTIONS, 'GET' );

		$this->assertEquals( self::TEST_NO_FINAL_URL_SUGGESTIONS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_assets_suggestions() {
		$params = [
			'id'   => 12345,
			'type' => 'post',
		];
		$this->assets_suggestions->expects( $this->once() )
			->method( 'get_assets_suggestions' )
			->willReturn( self::TEST_ASSETS_SUGGESTIONS );

		$response = $this->do_request(
			self::ROUTE_ASSETS_SUGGESTIONS,
			'GET',
			$params
		);

		$this->assertEquals( self::TEST_ASSETS_SUGGESTIONS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_assets_suggestions_with_invalid_id() {
		$params = [
			'id'   => 12345,
			'type' => 'post',
		];

		$this->assets_suggestions
			->method( 'get_assets_suggestions' )
			->willThrowException( new Exception( 'Invalid ID' ) );

		$response = $this->do_request( self::ROUTE_ASSETS_SUGGESTIONS, 'GET', $params );

		$this->assertEquals( 'Invalid ID', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}


}
