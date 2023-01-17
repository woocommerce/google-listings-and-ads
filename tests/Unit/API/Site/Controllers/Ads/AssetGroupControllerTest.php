<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AssetGroupController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;

/**
 * Class AssetSuggestionsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property RESTServer               $rest_server
 * @property AdsAssetGroup|MockObject $asset_group
 */
class AssetGroupControllerTest extends RESTControllerUnitTest {

	protected const TEST_CAMPAIGN_ID       = 1234567890;
	protected const TEST_ASSET_GROUP_ID    = 9876543210;
	protected const ROUTE_ASSET_GROUPS     = '/wc/gla/ads/campaigns/asset-groups';
	protected const TEST_NO_ASSET_GROUPS   = [];
	protected const TEST_ASSET_GROUPS_DATA = [
		[
			'id'               => self::TEST_ASSET_GROUP_ID,
			'final_url'        => 'https://test.com/shop',
			'display_url_path' => [ 'path1', 'path2' ],
			'assets'           => [
				'headline'               => [ 'Headline 1' ],
				'long_headline'          => [ 'Long Headline 1' ],
				'description'            => [ 'Description 1' ],
				'square_marketing_image' => [ 'https://test.com/image.png' ],
				'business_name'          => 'Test Blog',
			],
		],
	];

	public function setUp(): void {
		parent::setUp();

		$this->asset_group = $this->createMock( AdsAssetGroup::class );
		$this->controller  = new AssetGroupController( $this->server, $this->asset_group );
		$this->controller->register();
	}

	public function test_get_asset_groups() {
		$this->asset_group->expects( $this->once() )
			->method( 'get_asset_groups_by_campaign_id' )
			->with( self::TEST_CAMPAIGN_ID )
			->willReturn( self::TEST_ASSET_GROUPS_DATA );

		$response = $this->do_request( self::ROUTE_ASSET_GROUPS, 'GET', [ 'campaign_id' => self::TEST_CAMPAIGN_ID ] );

		$this->assertEquals( self::TEST_ASSET_GROUPS_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_no_asset_groups() {
		$this->asset_group->expects( $this->once() )
			->method( 'get_asset_groups_by_campaign_id' )
			->with( self::TEST_CAMPAIGN_ID )
			->willReturn( self::TEST_NO_ASSET_GROUPS );

		$response = $this->do_request( self::ROUTE_ASSET_GROUPS, 'GET', [ 'campaign_id' => self::TEST_CAMPAIGN_ID ] );

		$this->assertEquals( self::TEST_NO_ASSET_GROUPS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_asset_groups_exception() {
		$this->asset_group
			->method( 'get_asset_groups_by_campaign_id' )
			->willThrowException( new Exception( 'Account not connected' ) );

		$response = $this->do_request( self::ROUTE_ASSET_GROUPS, 'GET', [ 'campaign_id' => self::TEST_CAMPAIGN_ID ] );

		$this->assertEquals( 'Account not connected', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_edit_asset_group() {
		$this->asset_group
			->method( 'edit_asset_group' )
			->willReturn( self::TEST_ASSET_GROUP_ID );

		$response = $this->do_request( self::ROUTE_ASSET_GROUPS . '/' . self::TEST_ASSET_GROUP_ID, 'PUT' );
		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully edited asset group.',
				'id'      => self::TEST_ASSET_GROUP_ID,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_asset_group_with_api_exception() {
		$asset_group_data = [
			'id'          => self::TEST_ASSET_GROUP_ID,
			'path1'       => 'test path1',
			'wrong_field' => 'should not be here',
		];

		$expected_asset_group_data = [ 'path1' => 'test path1' ];

		$this->asset_group->expects( $this->once() )
			->method( 'edit_asset_group' )
			->with( self::TEST_ASSET_GROUP_ID, $expected_asset_group_data, [] )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_ASSET_GROUPS . '/' . self::TEST_ASSET_GROUP_ID, 'PUT', $asset_group_data );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_create_asset_group() {
		$this->asset_group
			->method( 'create_asset_group' )
			->willReturn( self::TEST_CAMPAIGN_ID );

		$response = $this->do_request( self::ROUTE_ASSET_GROUPS, 'POST', [ 'campaign_id' => self::TEST_CAMPAIGN_ID ] );
		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully created asset group.',
				'id'      => self::TEST_CAMPAIGN_ID,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_asset_group_with_api_exception() {
		$this->asset_group->expects( $this->once() )
			->method( 'create_asset_group' )
			->with( self::TEST_CAMPAIGN_ID )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_ASSET_GROUPS, 'POST', [ 'campaign_id' => self::TEST_CAMPAIGN_ID ] );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}



}
