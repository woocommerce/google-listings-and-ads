<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Assets\AssetSuggestionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AssetSuggestionsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property RESTServer                         $rest_server
 * @property AssetSuggestionsService|MockObject $asset_suggestion
 */
class AssetSuggestionsControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_PAGE_SUGGESTIONS = '/wc/gla/assets/page/suggestions';
	protected const TEST_PAGE_SUGGESTIONS  = [
		[
			'id'        => 1,
			'type'      => 'post',
			'post_type' => 'product',
			'title'     => 'Beanie with Logo',
			'url'       => 'http://localhost:8082/shop/clothing/accessories/beanie-with-logo/',
		],
		[
			'id'        => 2,
			'type'      => 'term',
			'post_type' => null,
			'title'     => 'Clothing',
			'url'       => 'http://localhost:8082/product-category/clothing/',
		],

	];
	protected const TEST_NO_PAGE_SUGGESTIONS = [];

	public function setUp(): void {
		parent::setUp();

		$this->asset_suggestion = $this->createMock( AssetSuggestionsService::class );
		$this->controller       = new AssetSuggestionsController( $this->server, $this->asset_suggestion );
		$this->controller->register();
	}

	public function test_get_page_suggestions() {
		$this->asset_suggestion->expects( $this->once() )
			->method( 'get_pages_suggestions' )
			->willReturn( self::TEST_PAGE_SUGGESTIONS );

		$response = $this->do_request( self::ROUTE_PAGE_SUGGESTIONS, 'GET' );

		$this->assertEquals( self::TEST_PAGE_SUGGESTIONS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_page_suggestion_empty_set() {
		$this->asset_suggestion->expects( $this->once() )
			->method( 'get_pages_suggestions' )
			->willReturn( self::TEST_NO_PAGE_SUGGESTIONS );

		$response = $this->do_request( self::ROUTE_PAGE_SUGGESTIONS, 'GET' );

		$this->assertEquals( self::TEST_NO_PAGE_SUGGESTIONS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_page_with_api_exception() {
		$this->asset_suggestion->expects( $this->once() )
			->method( 'get_pages_suggestions' )
			->willThrowException( new Exception( 'error', 401 ) );

		$response = $this->do_request( self::ROUTE_PAGE_SUGGESTIONS, 'GET' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 401, $response->get_status() );
	}
}
