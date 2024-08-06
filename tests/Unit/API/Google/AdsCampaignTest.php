<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignBudget;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignCriterion;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignLabel;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdsCampaignTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|AdsAssetGroup $asset_group */
	protected $asset_group;

	/** @var MockObject|AdsCampaignBudget $budget */
	protected $budget;

	/** @var MockObject|AdsCampaignCriterion $criterion */
	protected $criterion;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var AdsCampaign $campaign */
	protected $campaign;

	/** @var Container $container */
	protected $container;

	/** @var GoogleHelper $google_helper */
	protected $google_helper;

	/** @var MockObject|AdsCampaignLabel $campaign_label */
	protected $campaign_label;

	/** @var WC $wc */
	protected $wc;

	protected const TEST_CAMPAIGN_ID = 1234567890;
	protected const BASE_COUNTRY     = 'US';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->asset_group    = $this->createMock( AdsAssetGroup::class );
		$this->budget         = $this->createMock( AdsCampaignBudget::class );
		$this->campaign_label = $this->createMock( AdsCampaignLabel::class );
		$this->criterion      = new AdsCampaignCriterion();
		$this->options        = $this->createMock( OptionsInterface::class );
		$this->transients     = $this->createMock( TransientsInterface::class );

		$this->wc            = $this->createMock( WC::class );
		$this->google_helper = new GoogleHelper( $this->wc );

		$this->container = new Container();
		$this->container->share( AdsAssetGroup::class, $this->asset_group );
		$this->container->share( TransientsInterface::class, $this->transients );
		$this->container->share( WC::class, $this->wc );

		$this->campaign = new AdsCampaign( $this->client, $this->budget, $this->criterion, $this->google_helper, $this->campaign_label );
		$this->campaign->set_options_object( $this->options );
		$this->campaign->set_container( $this->container );

		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );
	}

	public function test_get_campaigns_empty_list() {
		$this->generate_ads_campaign_query_mock_with_no_campaigns();
		$this->assertEquals( [], $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns() {
		$campaign_criterion_data = [
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => 'geoTargetConstants/2158',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/2344',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/2826',
			],
		];

		$campaigns_data = [
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'name'               => 'Campaign One',
				'status'             => 'paused',
				'type'               => 'shopping',
				'amount'             => 10,
				'country'            => 'US',
				'targeted_locations' => [ 'TW' ],
			],
			[
				'id'                 => 5678901234,
				'name'               => 'Campaign Two',
				'status'             => 'enabled',
				'type'               => 'performance_max',
				'amount'             => 20,
				'country'            => 'UK',
				'targeted_locations' => [ 'HK', 'GB' ],
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, $campaign_criterion_data );
		$this->assertEquals( $campaigns_data, $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns_with_search_args() {
		$campaign_criterion_data = [
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => 'geoTargetConstants/2158',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/2344',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/2826',
			],
		];

		$campaigns_data = [
			[
				'id'                 => self::TEST_CAMPAIGN_ID,
				'name'               => 'Campaign One',
				'status'             => 'paused',
				'type'               => 'shopping',
				'amount'             => 10,
				'country'            => 'US',
				'targeted_locations' => [ 'TW' ],
			],
			[
				'id'                 => 5678901234,
				'name'               => 'Campaign Two',
				'status'             => 'enabled',
				'type'               => 'performance_max',
				'amount'             => 20,
				'country'            => 'UK',
				'targeted_locations' => [ 'HK', 'GB' ],
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, $campaign_criterion_data, true );

		$matcher = $this->exactly( 2 );
		$this->service_client
		->expects( $matcher )
		->method( 'search' )
		->willReturnCallback(
			function ( $request ) use ( $matcher ) {
				if ( $matcher->getInvocationCount() === 1 ) {
					$this->assertEquals( 2, $request->getPageSize() ); // Campaigns
				}

				if ( $matcher->getInvocationCount() === 2 ) {
					$this->assertEquals( 0, $request->getPageSize() ); // Criterions
				}

				return true;
			}
		);

		$this->assertEquals(
			[
				'campaigns'       => $campaigns_data,
				'total_results'   => count( $campaigns_data ),
				'next_page_token' => '',
			],
			$this->campaign->get_campaigns( true, true, [ 'per_page' => 2 ], true )
		);
	}

	public function test_get_campaigns_with_nonexist_location_id() {
		$campaign_criterion_data = [
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => 'geoTargetConstants/999999999',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/999999999',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/999999999',
			],
		];

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

		$this->generate_ads_campaign_query_mock( $campaigns_data, $campaign_criterion_data );
		$this->assertEquals( $campaigns_data, $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns_with_invalid_location_id() {
		$campaign_criterion_data = [
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => 'unknownResource1/2158',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'unknownResource2/2344',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'unknownResource3/2826',
			],
		];

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

		$this->generate_ads_campaign_query_mock( $campaigns_data, $campaign_criterion_data );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid geo target location ID' );

		$this->campaign->get_campaigns();
	}

	public function test_get_campaigns_exception() {
		$this->generate_ads_query_mock_exception( new ApiException( 'unavailable', 14, 'UNAVAILABLE' ) );

		try {
			$this->campaign->get_campaigns();
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error retrieving campaigns: unavailable',
					'errors'  => [ 'UNAVAILABLE' => 'unavailable' ],
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 503, $e->getCode() );
		}
	}

	public function test_get_campaign() {
		$campaign_criterion_data = [
			'campaign_id'         => self::TEST_CAMPAIGN_ID,
			'geo_target_constant' => 'geoTargetConstants/2158',
		];

		$campaign_data = [
			'id'                 => self::TEST_CAMPAIGN_ID,
			'name'               => 'Single Campaign',
			'status'             => 'enabled',
			'type'               => 'performance_max',
			'amount'             => 10,
			'country'            => 'US',
			'targeted_locations' => [ 'TW' ],
		];

		$this->generate_ads_campaign_query_mock( [ $campaign_data ], [ $campaign_criterion_data ] );
		$this->assertEquals( $campaign_data, $this->campaign->get_campaign( self::TEST_CAMPAIGN_ID ) );
	}

	public function test_get_campaign_exception() {
		$this->generate_ads_query_mock_exception( new ApiException( 'not found', 5, 'NOT_FOUND' ) );

		try {
			$this->campaign->get_campaign( self::TEST_CAMPAIGN_ID );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error retrieving campaign: not found',
					'errors'  => [ 'NOT_FOUND' => 'not found' ],
					'id'      => self::TEST_CAMPAIGN_ID,
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 404, $e->getCode() );
		}
	}

	public function test_create_campaign() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'US', 'GB' ],
		];

		$this->wc->expects( $this->once() )
			->method( 'get_base_country' )
			->willReturn( self::BASE_COUNTRY );

		$this->generate_campaign_mutate_mock( 'create', self::TEST_CAMPAIGN_ID );

		$expected = [
			'id'      => self::TEST_CAMPAIGN_ID,
			'status'  => 'enabled',
			'type'    => 'performance_max',
			'country' => self::BASE_COUNTRY,
		] + $campaign_data;

		$this->transients->expects( $this->once() )->method( 'delete' )->with( TransientsInterface::ADS_CAMPAIGN_COUNT );

		$this->assertEquals(
			$expected,
			$this->campaign->create_campaign( $campaign_data )
		);
	}

	public function test_create_campaign_null_location_id() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'Null location' ],
		];

		$this->wc->expects( $this->once() )
			->method( 'get_base_country' )
			->willReturn( self::BASE_COUNTRY );

		$this->generate_campaign_mutate_mock( 'create', self::TEST_CAMPAIGN_ID );

		$expected = [
			'id'      => self::TEST_CAMPAIGN_ID,
			'status'  => 'enabled',
			'type'    => 'performance_max',
			'country' => self::BASE_COUNTRY,
		] + $campaign_data;

		$this->assertEquals(
			$expected,
			$this->campaign->create_campaign( $campaign_data )
		);
	}

	public function test_create_campaign_exception_duplicate_campaign_name() {
		$campaign_data = [
			'name'               => 'Invalid Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'US', 'GB' ],
		];

		$errors = [
			'errors' => [
				[
					'errorCode' => [
						'campaignError' => 'DUPLICATE_CAMPAIGN_NAME',
					],
					'message'   => 'Duplicate campaign name',
				],
			],
		];

		$this->generate_campaign_mutate_mock_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT', [ 'metadata' => [ $errors ] ] )
		);

		try {
			$this->campaign->create_campaign( $campaign_data );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'A campaign with this name already exists',
					'errors'  => [
						'DUPLICATE_CAMPAIGN_NAME' => 'Duplicate campaign name',
						'INVALID_ARGUMENT'        => 'invalid',
					],
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 400, $e->getCode() );
		}
	}

	public function test_create_campaign_exception_invalid_location_id() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'Invalid location' ],
		];

		$errors = [
			'errors' => [
				[
					'errorCode' => [
						'campaignCriterionError' => 'INVALID_CRITERION_ID',
					],
					'message'   => 'Invalid criterion ID',
				],
			],
		];

		$this->generate_campaign_mutate_mock_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT', [ 'metadata' => [ $errors ] ] )
		);

		try {
			$this->campaign->create_campaign( $campaign_data );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error creating campaign: Invalid criterion ID',
					'errors'  => [
						'INVALID_CRITERION_ID' => 'Invalid criterion ID',
						'INVALID_ARGUMENT'     => 'invalid',
					],
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 400, $e->getCode() );
		}
	}

	public function test_edit_campaign() {
		$campaign_data = [
			'amount' => 40,
			'status' => 'paused',
		];

		$this->generate_campaign_mutate_mock( 'update', self::TEST_CAMPAIGN_ID );

		$this->assertEquals(
			self::TEST_CAMPAIGN_ID,
			$this->campaign->edit_campaign( self::TEST_CAMPAIGN_ID, $campaign_data )
		);
	}

	public function test_edit_campaign_exception() {
		$campaign_data = [
			'amount' => 0.001,
		];

		$this->generate_campaign_mutate_mock_exception( new ApiException( 'invalid', 3, 'INVALID_ARGUMENT' ) );

		try {
			$this->campaign->edit_campaign( self::TEST_CAMPAIGN_ID, $campaign_data );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error editing campaign: invalid',
					'errors'  => [ 'INVALID_ARGUMENT' => 'invalid' ],
					'id'      => self::TEST_CAMPAIGN_ID,
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 400, $e->getCode() );
		}
	}

	public function test_delete_campaign() {
		$this->generate_campaign_mutate_mock( 'remove', self::TEST_CAMPAIGN_ID );

		$this->transients->expects( $this->once() )->method( 'delete' )->with( TransientsInterface::ADS_CAMPAIGN_COUNT );

		$this->assertEquals(
			self::TEST_CAMPAIGN_ID,
			$this->campaign->delete_campaign( self::TEST_CAMPAIGN_ID )
		);
	}

	public function test_delete_campaign_exception() {
		$errors = [
			'errors' => [
				[
					'errorCode' => [
						'campaignError' => 'OPERATION_NOT_PERMITTED_FOR_REMOVED_RESOURCE',
					],
					'message'   => 'Campaign already deleted',
				],
			],
		];

		$this->generate_campaign_mutate_mock_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT', [ 'metadata' => [ $errors ] ] )
		);

		try {
			$this->campaign->delete_campaign( self::TEST_CAMPAIGN_ID );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'This campaign has already been deleted',
					'errors'  => [
						'OPERATION_NOT_PERMITTED_FOR_REMOVED_RESOURCE' => 'Campaign already deleted',
						'INVALID_ARGUMENT' => 'invalid',
					],
					'id'      => self::TEST_CAMPAIGN_ID,
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 400, $e->getCode() );
		}
	}

	public function test_get_campaign_convert_status_unconverted() {
		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Campaign One',
				'status'  => 'enabled',
				'type'    => CampaignType::SHOPPING,
				'amount'  => 10,
				'country' => 'US',
			],
			[
				'id'      => 5678901234,
				'name'    => 'Campaign Two',
				'status'  => 'enabled',
				'type'    => CampaignType::PERFORMANCE_MAX,
				'amount'  => 20,
				'country' => 'UK',
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, [] );
		$this->assertEquals( 'unconverted', $this->campaign->get_campaign_convert_status() );
	}

	public function test_get_campaign_convert_status_converted() {
		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Test Campaign',
				'status'  => 'removed',
				'type'    => CampaignType::SHOPPING,
				'amount'  => 10,
				'country' => 'US',
			],
			[
				'id'      => 5678901234,
				'name'    => 'Test Campaign',
				'status'  => 'enabled',
				'type'    => CampaignType::PERFORMANCE_MAX,
				'amount'  => 10,
				'country' => 'US',
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, [] );
		$this->assertEquals( 'converted', $this->campaign->get_campaign_convert_status() );
	}

	public function test_get_campaign_convert_status_not_applicable() {
		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Campaign One',
				'status'  => 'removed',
				'type'    => CampaignType::PERFORMANCE_MAX,
				'amount'  => 10,
				'country' => 'US',
			],
			[
				'id'      => 5678901234,
				'name'    => 'Campaign Two',
				'status'  => 'enabled',
				'type'    => CampaignType::PERFORMANCE_MAX,
				'amount'  => 10,
				'country' => 'US',
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, [] );
		$this->assertEquals( 'not-applicable', $this->campaign->get_campaign_convert_status() );
	}

	public function test_get_campaign_convert_status_exception() {
		$this->generate_ads_query_mock_exception( new ApiException( 'unavailable', 14, 'UNAVAILABLE' ) );
		$this->assertEquals( 'unknown', $this->campaign->get_campaign_convert_status() );
	}

	public function test_get_campaign_convert_status_fetch_cached() {
		$this->options->method( 'get' )
			->with( OptionsInterface::CAMPAIGN_CONVERT_STATUS )
			->willReturn(
				[
					'status'  => 'unconverted',
					'updated' => time(),
				]
			);

		$this->assertEquals( 'unconverted', $this->campaign->get_campaign_convert_status() );
	}

	public function test_get_campaign_convert_status_refresh_cache() {
		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Test Campaign',
				'status'  => 'enabled',
				'type'    => CampaignType::PERFORMANCE_MAX,
				'amount'  => 10,
				'country' => 'US',
			],
		];

		$this->options->method( 'get' )
			->with( OptionsInterface::CAMPAIGN_CONVERT_STATUS )
			->willReturn(
				[
					'status'  => 'unknown',
					'updated' => time() - WEEK_IN_SECONDS,
				]
			);

		$this->generate_ads_campaign_query_mock( $campaigns_data, [] );
		$this->assertEquals( 'not-applicable', $this->campaign->get_campaign_convert_status() );
	}

	public function test_get_campaign_convert_status_refresh_cache_no_update_time() {
		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Test Campaign',
				'status'  => 'enabled',
				'type'    => CampaignType::SHOPPING,
				'amount'  => 10,
				'country' => 'US',
			],
		];

		$this->options->method( 'get' )
			->with( OptionsInterface::CAMPAIGN_CONVERT_STATUS )
			->willReturn(
				[
					'status' => 'unknown',
				]
			);

		$this->generate_ads_campaign_query_mock( $campaigns_data, [] );
		$this->assertEquals( 'unconverted', $this->campaign->get_campaign_convert_status() );
	}

	public function test_create_campaign_with_label() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'US', 'GB' ],
			'label'              => 'wc-gla',
		];

		$this->wc->expects( $this->once() )
			->method( 'get_base_country' )
			->willReturn( self::BASE_COUNTRY );

		$this->generate_campaign_mutate_mock( 'create', self::TEST_CAMPAIGN_ID );

		$expected = [
			'id'      => self::TEST_CAMPAIGN_ID,
			'status'  => 'enabled',
			'type'    => 'performance_max',
			'country' => self::BASE_COUNTRY,
		] + $campaign_data;

		$this->transients->expects( $this->once() )->method( 'delete' )->with( TransientsInterface::ADS_CAMPAIGN_COUNT );
		$this->campaign_label->expects( $this->once() )
			->method( 'assign_label_to_campaign_by_label_name' )
			->with( self::TEST_CAMPAIGN_ID, 'wc-gla' );

		$this->assertEquals(
			$expected,
			$this->campaign->create_campaign( $campaign_data )
		);
	}

	public function test_create_campaign_throws_exception() {
		$campaign_data = [
			'name'               => 'New Campaign',
			'amount'             => 20,
			'targeted_locations' => [ 'US', 'GB' ],
			'label'              => 'wc-gla',
		];

		$this->wc->expects( $this->once() )
			->method( 'get_base_country' )
			->willReturn( self::BASE_COUNTRY );

		$this->generate_campaign_mutate_mock( 'create', self::TEST_CAMPAIGN_ID );

		$this->transients->expects( $this->never() )->method( 'delete' );
		$this->campaign_label->expects( $this->once() )
			->method( 'assign_label_to_campaign_by_label_name' )
			->willThrowException( new ApiException( 'label not found', 5, 'NOT_FOUND' ) );

		try {
			$this->campaign->create_campaign( $campaign_data );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertEquals(
				[
					'message' => 'Error creating campaign: label not found',
					'errors'  => [ 'NOT_FOUND' => 'label not found' ],
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 404, $e->getCode() );
		}
	}
}
