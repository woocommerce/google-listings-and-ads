<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Assets\AssetSuggestionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AssetSuggestionsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property RESTServer                         $rest_server
 * @property AssetSuggestionsService|MockObject $asset_suggestions
 */
class AssetSuggestionsControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_FINAL_URL_SUGGESTIONS = '/wc/gla/assets/final-url/suggestions';
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

	public function setUp(): void {
		parent::setUp();

		$this->asset_suggestions = $this->createMock( AssetSuggestionsService::class );
		$this->controller        = new AssetSuggestionsController( $this->server, $this->asset_suggestions );
		$this->controller->register();
	}

	public function test_get_final_url_suggestions() {
		$this->asset_suggestions->expects( $this->once() )
			->method( 'get_final_url_suggestions' )
			->willReturn( self::TEST_FINAL_URL_SUGGESTIONS );

		$response = $this->do_request( self::ROUTE_FINAL_URL_SUGGESTIONS, 'GET' );

		$this->assertEquals( self::TEST_FINAL_URL_SUGGESTIONS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_final_url_suggestions_empty_set() {
		$this->asset_suggestions->expects( $this->once() )
			->method( 'get_final_url_suggestions' )
			->willReturn( self::TEST_NO_FINAL_URL_SUGGESTIONS );

		$response = $this->do_request( self::ROUTE_FINAL_URL_SUGGESTIONS, 'GET' );

		$this->assertEquals( self::TEST_NO_FINAL_URL_SUGGESTIONS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}
}
