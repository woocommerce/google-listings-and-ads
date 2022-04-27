<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\CampaignController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CampaignControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property AdsCampaign|MockObject         $ads_campaign
 * @property ISO3166DataProvider|MockObject $iso_provider;
 * @property CampaignController             $controller
 */
class CampaignControllerTest extends RESTControllerUnitTest {

	protected const TEST_CAMPAIGN_ID = 1234567890;
	protected const ROUTE_CAMPAIGNS  = '/wc/gla/ads/campaigns';
	protected const ROUTE_CAMPAIGN   = '/wc/gla/ads/campaigns/' . self::TEST_CAMPAIGN_ID;
	protected const BASE_COUNTRY     = 'US';

	public function setUp(): void {
		parent::setUp();

		$this->ads_campaign = $this->createMock( AdsCampaign::class );
		$this->iso_provider = $this->createMock( ISO3166DataProvider::class );

		$this->country_supported = true;

		$this->google_helper = $this->createMock( GoogleHelper::class );
		$this->google_helper->method( 'is_country_supported' )
			->willReturnCallback( function () { return $this->country_supported; } );

		$this->controller = new CampaignController( $this->server, $this->ads_campaign );
		$this->controller->register();
		$this->controller->set_iso3166_provider( $this->iso_provider );
		$this->controller->set_google_helper_object( $this->google_helper );
	}

	public function test_get_campaigns() {
		$campaigns_data = [
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'name'               => 'Campaign One',
				'status'             => 'paused',
				'type'               => 'shopping',
				'amount'             => 10,
				'country'            => 'US',
				'targeted_locations' => [],
			],
			[
				'id'                 => 5678901234,
				'name'               => 'Campaign Two',
				'status'             => 'enabled',
				'type'               => 'performance_max',
				'amount'             => 20,
				'country'            => 'UK',
				'targeted_locations' => [],
			],
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaigns' )
			->willReturn( $campaigns_data );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'GET' );

		$this->assertEquals( $campaigns_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_campaigns_converted_names() {
		$campaigns_data = [
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'name'               => 'Test Campaign',
				'status'             => 'removed',
				'type'               => 'shopping',
				'amount'             => 10,
				'country'            => 'US',
				'targeted_locations' => [],
			],
			[
				'id'                 => 5678901234,
				'name'               => 'Test Campaign',
				'status'             => 'enabled',
				'type'               => 'performance_max',
				'amount'             => 20,
				'country'            => 'UK',
				'targeted_locations' => [],
			],
		];

		$expected = [
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'name'               => 'Test Campaign (Old)',
				'status'             => 'removed',
				'type'               => 'shopping',
				'amount'             => 10,
				'country'            => 'US',
				'targeted_locations' => [],
			],
			[
				'id'                 => 5678901234,
				'name'               => 'Test Campaign',
				'status'             => 'enabled',
				'type'               => 'performance_max',
				'amount'             => 20,
				'country'            => 'UK',
				'targeted_locations' => [],
			],
		];

		$this->ads_campaign
			->method( 'get_campaign_convert_status' )
			->willReturn( 'converted' );

		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaigns' )
			->with( false )
			->willReturn( $campaigns_data );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'GET', [ 'exclude_removed' => false ] );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_campaigns_with_api_exception() {
		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaigns' )
			->willThrowException( new Exception( 'error', 401 ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'GET' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_create_campaign() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => ['US', 'GB', 'TW'],
		];

		$expected = [
			'id'      => self::TEST_CAMPAIGN_ID,
			'status'  => 'enabled',
			'type'    => 'performance_max',
			'country' => self::BASE_COUNTRY,
		] + $campaign_data;

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->with( $campaign_data )
			->willReturn( $expected );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_campaign_without_name() {
		$campaign_data = [
			'amount'             => 30,
			'targeted_locations' => ['US', 'GB', 'TW'],
		];

		$expected = [
			'name'    => 'Campaign 2022-02-22 02:22:02',
			'id'      => self::TEST_CAMPAIGN_ID,
			'status'  => 'enabled',
			'type'    => 'performance_max',
			'country' => self::BASE_COUNTRY,
		] + $campaign_data;

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->willReturnCallback(
				function( array $data ) use ( $campaign_data, $expected ) {
					$name = $data['name'];
					unset( $data['name'] );

					// Confirm name matches the datetime format.
					$this->assertStringMatchesFormat( 'Campaign %d-%d-%d %d:%d:%d', $name, 'Name does not match' );
					$this->assertEquals( $data, $campaign_data );

					return $expected;
				}
			);

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_campaign_invalid_country_code() {
		$campaign_data = [
			'amount'             => 20,
			'targeted_locations' => ['United States'],
		];

		$this->iso_provider
			->method( 'alpha2' )
			->willThrowException( new Exception( 'invalid_country' ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): targeted_locations', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_create_campaign_unsupported_country() {
		$this->country_supported = false;

		$campaign_data = [
			'amount'             => 20,
			'targeted_locations' => ['CN', 'KP'],
		];

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): targeted_locations', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_create_campaign_empty_targeted_locations() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [],
		];

		$expected = [
			'id'      => self::TEST_CAMPAIGN_ID,
			'status'  => 'enabled',
			'country' => self::BASE_COUNTRY,
		] + $campaign_data;

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): targeted_locations', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_create_campaign_with_api_exception() {
		$campaign_data = [
			'amount'  => 20,
			'targeted_locations' => ['US'],
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->willThrowException( new Exception( 'error', 401 ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_create_campaign_missing_parameters() {
		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', [] );

		$this->assertEquals( 'Missing parameter(s): amount, targeted_locations', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_campaign() {
		$campaign_data = [
			'id'                 => self::TEST_CAMPAIGN_ID,
			'name'               => 'Campaign Name',
			'status'             => 'enabled',
			'type'               => 'performance_max',
			'amount'             => 10,
			'country'            => 'US',
			'targeted_locations' => [],
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaign' )
			->with( self::TEST_CAMPAIGN_ID )
			->willReturn( $campaign_data );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'GET' );

		$this->assertEquals( $campaign_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_campaign_not_found() {
		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaign' )
			->with( self::TEST_CAMPAIGN_ID )
			->willReturn( [] );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'GET' );

		$this->assertEquals(
			[
				'message' => 'Campaign is not available.',
				'id'      => self::TEST_CAMPAIGN_ID,
			],
			$response->get_data()
		);
		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_get_campaign_with_api_exception() {
		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaign' )
			->with( self::TEST_CAMPAIGN_ID )
			->willThrowException( new Exception( 'error', 500 ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'GET' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 500, $response->get_status() );
	}

	public function test_edit_campaign() {
		$campaign_data = [
			'amount'  => 44.55,
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'edit_campaign' )
			->with( self::TEST_CAMPAIGN_ID, $campaign_data )
			->willReturn( self::TEST_CAMPAIGN_ID );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'POST', $campaign_data );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully edited campaign.',
				'id'      => self::TEST_CAMPAIGN_ID,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_campaign_country() {
		$campaign_data = [
			'country' => 'GB',
		];

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'POST', $campaign_data );

		$this->assertEquals(
			[
				'status'  => 'invalid_data',
				'message' => 'Invalid edit data.',
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_edit_campaign_with_api_exception() {
		$campaign_data = [
			'amount' => 0.001,
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'edit_campaign' )
			->with( self::TEST_CAMPAIGN_ID, $campaign_data )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'POST', $campaign_data );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_delete_campaign() {
		$this->ads_campaign->expects( $this->once() )
			->method( 'delete_campaign' )
			->with( self::TEST_CAMPAIGN_ID )
			->willReturn( self::TEST_CAMPAIGN_ID );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'DELETE' );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully deleted campaign.',
				'id'      => self::TEST_CAMPAIGN_ID,
			], $response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_delete_campaign_with_api_exception() {
		$this->ads_campaign->expects( $this->once() )
			->method( 'delete_campaign' )
			->with( self::TEST_CAMPAIGN_ID )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'DELETE' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
