<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdvertisingSource;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\CampaignController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\TrackingTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CampaignControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class CampaignControllerTest extends RESTControllerUnitTest {

	use TrackingTrait;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

	/** @var MockObject|ISO3166DataProvider $iso_provider */
	protected $iso_provider;

	/** @var MockObject|GoogleHelper $google_helper */
	protected $google_helper;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var CampaignController $controller */
	protected $controller;

	/** @var int $merchant_id */
	protected $merchant_id;

	/** @var bool $country_supported */
	protected $country_supported;

	protected const TEST_CAMPAIGN_ID = 1234567890;
	protected const ROUTE_CAMPAIGNS  = '/wc/gla/ads/campaigns';
	protected const ROUTE_CAMPAIGN   = '/wc/gla/ads/campaigns/' . self::TEST_CAMPAIGN_ID;
	protected const BASE_COUNTRY     = 'US';
	protected const MERCHANT_ID      = 1234;

	public function setUp(): void {
		parent::setUp();

		$this->ads_campaign = $this->createMock( AdsCampaign::class );
		$this->iso_provider = $this->createMock( ISO3166DataProvider::class );

		$this->country_supported = true;

		$this->google_helper = $this->createMock( GoogleHelper::class );
		$this->google_helper->method( 'is_country_supported' )
			->willReturnCallback(
				function () {
					return $this->country_supported;
				}
			);

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturnCallback(
			function () {
				return $this->merchant_id;
			}
		);

		$this->merchant_id = self::MERCHANT_ID;

		$this->controller = new CampaignController( $this->server, $this->ads_campaign );
		$this->controller->set_options_object( $this->options );
		$this->controller->register();
		$this->controller->set_iso3166_provider( $this->iso_provider );
		$this->controller->set_google_helper_object( $this->google_helper );
	}

	public function test_get_campaigns() {
		$campaigns_data = [
			[
				'id'                                    => self::TEST_CAMPAIGN_ID,
				'name'                                  => 'Campaign One',
				'status'                                => 'paused',
				'type'                                  => 'shopping',
				'amount'                                => 10,
				'country'                               => 'US',
				'targeted_locations'                    => [],
				'eu_political_advertising_confirmation' => false,
				'start_date'                            => '2025-01-15',
			],
			[
				'id'                                    => 5678901234,
				'name'                                  => 'Campaign Two',
				'status'                                => 'enabled',
				'type'                                  => 'performance_max',
				'amount'                                => 20,
				'country'                               => 'UK',
				'targeted_locations'                    => [],
				'eu_political_advertising_confirmation' => false,
				'start_date'                            => '2025-01-20',
			],
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaigns' )
			->willReturn( $campaigns_data );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'GET' );

		$data = $response->get_data();
		$this->assertEquals( $campaigns_data, $data );
		$this->assertEquals( 200, $response->get_status() );
		foreach ( $data as $campaign ) {
			$this->assertArrayHasKey( 'start_date', $campaign );
			if ( $campaign['start_date'] !== null ) {
				$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $campaign['start_date'] );
			}
		}
	}

	public function test_get_campaigns_converted_names() {
		$campaigns_data = [
			[
				'id'                                    => self::TEST_CAMPAIGN_ID,
				'name'                                  => 'Test Campaign',
				'status'                                => 'removed',
				'type'                                  => 'shopping',
				'amount'                                => 10,
				'country'                               => 'US',
				'targeted_locations'                    => [],
				'eu_political_advertising_confirmation' => false,
				'start_date'                            => '2025-01-15',
			],
			[
				'id'                                    => 5678901234,
				'name'                                  => 'PMax: Test Campaign',
				'status'                                => 'enabled',
				'type'                                  => 'performance_max',
				'amount'                                => 20,
				'country'                               => 'UK',
				'targeted_locations'                    => [],
				'eu_political_advertising_confirmation' => false,
				'start_date'                            => '2025-01-20',
			],
		];

		$expected = [
			[
				'id'                                    => self::TEST_CAMPAIGN_ID,
				'name'                                  => 'Test Campaign',
				'status'                                => 'removed',
				'type'                                  => 'shopping',
				'amount'                                => 10,
				'country'                               => 'US',
				'targeted_locations'                    => [],
				'eu_political_advertising_confirmation' => false,
				'start_date'                            => '2025-01-15',
			],
			[
				'id'                                    => 5678901234,
				'name'                                  => 'PMax: Test Campaign',
				'status'                                => 'enabled',
				'type'                                  => 'performance_max',
				'amount'                                => 20,
				'country'                               => 'UK',
				'targeted_locations'                    => [],
				'eu_political_advertising_confirmation' => false,
				'start_date'                            => '2025-01-20',
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
			'targeted_locations' => [ 'US', 'GB', 'TW' ],
			'label'              => 'wc-web',
		];

		$expected = [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'country'                               => self::BASE_COUNTRY,
			'eu_political_advertising_confirmation' => false,
			'start_date'                            => null,
		] + array_diff_key( $campaign_data, [ 'label' => 'wc-web' ] );

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->with( $campaign_data )
			->willReturn( array_diff_key( $expected, [ 'start_date' => null ] ) );

		$this->expect_track_event(
			'created_campaign',
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'status'             => 'enabled',
				'name'               => 'New Campaign',
				'amount'             => 20,
				'country'            => self::BASE_COUNTRY,
				'targeted_locations' => 'US,GB,TW',
				'source'             => 'wc-web',
			]
		);

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_campaign_with_label() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'US', 'GB', 'TW' ],
		];

		$expected = [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'country'                               => self::BASE_COUNTRY,
			'name'                                  => 'New Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'US', 'GB', 'TW' ],
			'eu_political_advertising_confirmation' => false,
			'start_date'                            => null,
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->with( $campaign_data )
			->willReturn( array_diff_key( $expected, [ 'start_date' => null ] ) );

		$this->expect_track_event(
			'created_campaign',
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'status'             => 'enabled',
				'name'               => 'New Campaign',
				'amount'             => 20,
				'country'            => self::BASE_COUNTRY,
				'targeted_locations' => 'US,GB,TW',
				'source'             => '',
			]
		);

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_campaign_without_name() {
		$campaign_data = [
			'amount'             => 30,
			'targeted_locations' => [ 'US', 'GB', 'TW' ],
		];

		$expected = [
			'name'                                  => 'Campaign 2022-02-22 02:22:02',
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'country'                               => self::BASE_COUNTRY,
			'eu_political_advertising_confirmation' => false,
			'start_date'                            => null,
		] + $campaign_data;

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->willReturnCallback(
				function ( array $data ) use ( $campaign_data, $expected ) {
					$name = $data['name'];
					unset( $data['name'] );

					// Confirm name matches the datetime format.
					$this->assertStringMatchesFormat( 'Campaign %d-%d-%d %d:%d:%d', $name, 'Name does not match' );
					$this->assertEquals( $data, $campaign_data );

					return array_diff_key( $expected, [ 'start_date' => null ] );
				}
			);

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_campaign_invalid_country_code() {
		$campaign_data = [
			'amount'             => 20,
			'targeted_locations' => [ 'United States' ],
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
			'targeted_locations' => [ 'CN', 'KP' ],
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

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): targeted_locations', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_create_campaign_with_api_exception() {
		$campaign_data = [
			'amount'             => 20,
			'targeted_locations' => [ 'US' ],
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

	public function test_create_campaign_with_final_url_and_assets() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 50,
			'targeted_locations' => [ 'US', 'GB' ],
			'final_url'          => 'https://example.com',
			'assets'             => [
				[
					'field_type' => 'headline',
					'content'    => 'Test headline',
				],
				[
					'field_type' => 'square_marketing_image',
					'content'    => 'https://example.com/image.jpg',
				],
			],
		];

		$expected = [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'country'                               => self::BASE_COUNTRY,
			'name'                                  => 'New Campaign',
			'amount'                                => 50,
			'targeted_locations'                    => [ 'US', 'GB' ],
			'eu_political_advertising_confirmation' => false,
			'start_date'                            => null,
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->with( $campaign_data )
			->willReturn( $expected );

		$this->expect_track_event(
			'created_campaign',
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'status'             => 'enabled',
				'name'               => 'New Campaign',
				'amount'             => 50,
				'country'            => self::BASE_COUNTRY,
				'targeted_locations' => 'US,GB',
				'source'             => '',
			]
		);

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( $expected, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );

		// Final URL and assets should not be returned in response.
		$this->assertArrayNotHasKey( 'final_url', $response->get_data() );
		$this->assertArrayNotHasKey( 'assets', $response->get_data() );
	}

	public function test_get_campaign() {
		$campaign_data = [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'name'                                  => 'Campaign Name',
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'amount'                                => 10,
			'country'                               => 'US',
			'targeted_locations'                    => [],
			'eu_political_advertising_confirmation' => false,
			'start_date'                            => '2025-01-15',
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaign' )
			->with( self::TEST_CAMPAIGN_ID )
			->willReturn( $campaign_data );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'GET' );

		$data = $response->get_data();
		$this->assertEquals( $campaign_data, $data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'start_date', $data );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $data['start_date'] );
	}

	public function test_get_campaign_returns_null_start_date_when_unavailable() {
		$campaign_data = [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'name'                                  => 'Campaign Without Start Date',
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'amount'                                => 10,
			'country'                               => 'US',
			'targeted_locations'                    => [],
			'eu_political_advertising_confirmation' => false,
			'start_date'                            => null,
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'get_campaign' )
			->with( self::TEST_CAMPAIGN_ID )
			->willReturn( $campaign_data );

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'GET' );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'start_date', $data );
		$this->assertNull( $data['start_date'] );
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

	protected function get_created_campaign_response( array $campaign_data ): array {
		return [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'country'                               => self::BASE_COUNTRY,
			'eu_political_advertising_confirmation' => false,
		] + $campaign_data;
	}

	protected function get_new_campaign_data(): array {
		return [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'US' ],
		];
	}

	public function test_create_campaign_without_advertising_source_is_not_rejected_without_merchant_center() {
		$this->merchant_id = 0;
		$campaign_data     = $this->get_new_campaign_data();

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->with( $campaign_data )
			->willReturn( $this->get_created_campaign_response( $campaign_data ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_campaign_passes_a_page_advertising_source_with_merchant_center_connected() {
		$campaign_data = $this->get_new_campaign_data() + [ 'advertising_source' => AdvertisingSource::PAGE ];

		$this->ads_campaign->expects( $this->once() )
			->method( 'create_campaign' )
			->with( $campaign_data )
			->willReturn( $this->get_created_campaign_response( $campaign_data ) );

		$response = $this->do_request( self::ROUTE_CAMPAIGNS, 'POST', $campaign_data );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_campaign_rejects_an_invalid_advertising_source() {
		$this->ads_campaign->expects( $this->never() )->method( 'create_campaign' );

		$response = $this->do_request(
			self::ROUTE_CAMPAIGNS,
			'POST',
			$this->get_new_campaign_data() + [ 'advertising_source' => 'services' ]
		);

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_create_campaign_rejects_a_product_advertising_source_without_merchant_center() {
		$this->merchant_id = 0;

		$this->ads_campaign->expects( $this->never() )->method( 'create_campaign' );

		$response = $this->do_request(
			self::ROUTE_CAMPAIGNS,
			'POST',
			$this->get_new_campaign_data() + [ 'advertising_source' => AdvertisingSource::PRODUCT ]
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'merchant_center_required', $response->get_data()['code'] );
	}

	public function test_edit_campaign_ignores_the_advertising_source() {
		$this->ads_campaign->expects( $this->once() )
			->method( 'edit_campaign' )
			->with( self::TEST_CAMPAIGN_ID, [ 'amount' => 30 ] )
			->willReturn( self::TEST_CAMPAIGN_ID );

		$this->expect_track_event(
			'edited_campaign',
			[
				'id'     => self::TEST_CAMPAIGN_ID,
				'amount' => 30,
			]
		);

		$response = $this->do_request(
			self::ROUTE_CAMPAIGN,
			'POST',
			[
				'amount'             => 30,
				'advertising_source' => AdvertisingSource::PAGE,
			]
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_edit_campaign() {
		$campaign_data = [
			'amount' => 44.55,
		];

		$this->ads_campaign->expects( $this->once() )
			->method( 'edit_campaign' )
			->with( self::TEST_CAMPAIGN_ID, $campaign_data )
			->willReturn( self::TEST_CAMPAIGN_ID );

		$this->expect_track_event(
			'edited_campaign',
			[
				'id'     => self::TEST_CAMPAIGN_ID,
				'amount' => 44.55,
			]
		);

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

		$this->expect_track_event(
			'deleted_campaign',
			[
				'id' => self::TEST_CAMPAIGN_ID,
			]
		);

		$response = $this->do_request( self::ROUTE_CAMPAIGN, 'DELETE' );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully deleted campaign.',
				'id'      => self::TEST_CAMPAIGN_ID,
			],
			$response->get_data()
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
