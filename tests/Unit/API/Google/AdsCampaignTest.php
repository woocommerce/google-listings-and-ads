<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
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

	/** @var MockObject|AdsCampaignAsset $campaign_asset */
	protected $campaign_asset;

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
		$this->campaign_asset = $this->createMock( AdsCampaignAsset::class );
		$this->criterion      = new AdsCampaignCriterion();
		$this->options        = $this->createMock( OptionsInterface::class );
		$this->transients     = $this->createMock( TransientsInterface::class );

		$this->wc            = $this->createMock( WC::class );
		$this->google_helper = new GoogleHelper( $this->wc );

		$this->container = new Container();
		$this->container->addShared( AdsAssetGroup::class, $this->asset_group );
		$this->container->addShared( TransientsInterface::class, $this->transients );
		$this->container->addShared( WC::class, $this->wc );

		$this->campaign = new AdsCampaign( $this->client, $this->budget, $this->criterion, $this->google_helper, $this->campaign_label, $this->campaign_asset );
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
				'id'                                    => self::TEST_CAMPAIGN_ID,
				'name'                                  => 'Campaign One',
				'status'                                => 'paused',
				'type'                                  => 'shopping',
				'amount'                                => 10,
				'country'                               => 'US',
				'targeted_locations'                    => [ 'TW' ],
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
				'targeted_locations'                    => [ 'HK', 'GB' ],
				'eu_political_advertising_confirmation' => false,
				'start_date'                            => '2025-01-20',
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, $campaign_criterion_data );
		$this->assertEquals( $campaigns_data, $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns_with_limited_results() {
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

		$this->generate_ads_campaign_query_mock( $campaigns_data, [] );
		$this->assertEquals(
			array_slice( $campaigns_data, 0, 1 ), // Only expect one result.
			$this->campaign->get_campaigns(
				true,
				true,
				[ 'per_page' => 1 ],
			)
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
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'name'                                  => 'Single Campaign',
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'amount'                                => 10,
			'country'                               => 'US',
			'targeted_locations'                    => [ 'TW' ],
			'eu_political_advertising_confirmation' => false,
			'start_date'                            => '2025-01-15',
		];

		$this->generate_ads_campaign_query_mock( [ $campaign_data ], [ $campaign_criterion_data ] );
		$this->assertEquals( $campaign_data, $this->campaign->get_campaign( self::TEST_CAMPAIGN_ID ) );
	}

	public function test_get_campaign_returns_null_start_date_when_unavailable() {
		$campaign_criterion_data = [
			'campaign_id'         => self::TEST_CAMPAIGN_ID,
			'geo_target_constant' => 'geoTargetConstants/2158',
		];

		$campaign_data_without_start_date = [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'name'                                  => 'Campaign Without Start Date',
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'amount'                                => 10,
			'country'                               => 'US',
			'targeted_locations'                    => [ 'TW' ],
			'eu_political_advertising_confirmation' => false,
		];

		$expected               = $campaign_data_without_start_date;
		$expected['start_date'] = null;

		$this->generate_ads_campaign_query_mock( [ $campaign_data_without_start_date ], [ $campaign_criterion_data ] );
		$this->assertEquals( $expected, $this->campaign->get_campaign( self::TEST_CAMPAIGN_ID ) );
	}

	public function test_get_highest_spend_campaign_returns_cached_value() {
		$cached_campaign = [
			'id'      => 5678901234,
			'name'    => 'Cached Campaign',
			'status'  => 'enabled',
			'type'    => 'performance_max',
			'amount'  => 50,
			'country' => 'US',
		];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_HIGHEST_SPEND_CAMPAIGN )
			->willReturn( [ 'campaign' => $cached_campaign ] );
		$this->transients->expects( $this->never() )->method( 'set' );

		$this->assertEquals( $cached_campaign, $this->campaign->get_highest_spend_campaign() );
	}

	public function test_get_highest_spend_campaign_fetches_and_caches_on_miss() {
		$campaigns_data = [
			[
				'id'                                    => self::TEST_CAMPAIGN_ID,
				'name'                                  => 'Campaign One',
				'status'                                => 'paused',
				'type'                                  => 'performance_max',
				'amount'                                => 10,
				'country'                               => 'US',
				'targeted_locations'                    => [],
				'eu_political_advertising_confirmation' => false,
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
			],
		];

		$expected_highest = $campaigns_data[1];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_HIGHEST_SPEND_CAMPAIGN )
			->willReturn( null );
		// set() may be called twice: ADS_HIGHEST_SPEND_CAMPAIGN (our cache) and possibly ADS_CAMPAIGN_COUNT from get_campaigns().
		$this->transients->expects( $this->atLeastOnce() )
			->method( 'set' )
			->willReturnCallback(
				function ( string $name, $value, int $expiration = 0 ) use ( $expected_highest ) {
					if ( $name === TransientsInterface::ADS_HIGHEST_SPEND_CAMPAIGN ) {
						$this->assertIsArray( $value );
						$this->assertArrayHasKey( 'campaign', $value );
						$campaign = $value['campaign'];
						$this->assertEquals( $expected_highest['id'], $campaign['id'] );
						$this->assertEquals( $expected_highest['status'], $campaign['status'] );
						$this->assertEqualsWithDelta( $expected_highest['amount'], $campaign['amount'] ?? 0, 0.01 );
						$this->assertEquals( HOUR_IN_SECONDS * 12, $expiration );
					}
					return true;
				}
			);

		$this->generate_ads_campaign_query_mock( $campaigns_data, [] );

		$result = $this->campaign->get_highest_spend_campaign();
		$this->assertEquals( $expected_highest['id'], $result['id'] );
		$this->assertEquals( $expected_highest['status'], $result['status'] );
		$this->assertEqualsWithDelta( $expected_highest['amount'], $result['amount'] ?? 0, 0.01 );
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

	public function test_get_campaigns_missing_eu_political_declaration_skips_video_campaigns() {
		$campaigns_data = [
			[
				'id'      => 111,
				'name'    => 'Non-shopping PMax',
				'status'  => 'enabled',
				'type'    => CampaignType::PERFORMANCE_MAX,
				'country' => 'US',
				'amount'  => 10,
			],
			[
				'id'      => 222,
				'name'    => 'Video',
				'status'  => 'enabled',
				'type'    => CampaignType::VIDEO,
				'country' => 'US',
				'amount'  => 10,
			],
			[
				'id'      => 333,
				'name'    => 'Shopping',
				'status'  => 'enabled',
				'type'    => CampaignType::SHOPPING,
				'country' => 'US',
				'amount'  => 10,
			],
		];

		$rows = array_map( [ $this, 'generate_campaign_row_mock' ], $campaigns_data );
		$this->generate_ads_query_mock( $rows );

		$this->options->expects( $this->never() )
			->method( 'update' )
			->with( OptionsInterface::ADS_EU_POLITICAL_DECLARATIONS_COMPLETE );

		$result = $this->campaign->get_campaigns_missing_eu_political_declaration();

		$this->assertCount( 2, $result );
		$this->assertEquals(
			[
				'id'   => 111,
				'name' => 'Non-shopping PMax',
			],
			$result[0]
		);
		$this->assertEquals(
			[
				'id'   => 333,
				'name' => 'Shopping',
			],
			$result[1]
		);
	}

	public function test_get_campaigns_missing_eu_political_declaration_sets_complete_flag_when_empty() {
		$this->generate_ads_query_mock( [] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_EU_POLITICAL_DECLARATIONS_COMPLETE, true );

		$this->assertEquals( [], $this->campaign->get_campaigns_missing_eu_political_declaration() );
	}

	public function test_get_campaigns_missing_eu_political_declaration_sets_complete_flag_when_only_video_campaigns() {
		$campaigns_data = [
			[
				'id'      => 222,
				'name'    => 'Video',
				'status'  => 'enabled',
				'type'    => CampaignType::VIDEO,
				'country' => 'US',
				'amount'  => 10,
			],
		];

		$rows = array_map( [ $this, 'generate_campaign_row_mock' ], $campaigns_data );
		$this->generate_ads_query_mock( $rows );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::ADS_EU_POLITICAL_DECLARATIONS_COMPLETE, true );

		$this->assertEquals( [], $this->campaign->get_campaigns_missing_eu_political_declaration() );
	}

	public function test_create_campaign() {
		$campaign_data = [
			'name'                                  => 'New Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'US', 'GB' ],
			'eu_political_advertising_confirmation' => false,
		];

		$this->wc->expects( $this->once() )
			->method( 'get_base_country' )
			->willReturn( self::BASE_COUNTRY );

		$this->generate_campaign_mutate_mock( 'create', self::TEST_CAMPAIGN_ID );

		$expected = [
			'id'                                    => self::TEST_CAMPAIGN_ID,
			'status'                                => 'enabled',
			'type'                                  => 'performance_max',
			'country'                               => self::BASE_COUNTRY,
			'eu_political_advertising_confirmation' => false,
		] + $campaign_data;

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->withConsecutive(
				[ TransientsInterface::ADS_CAMPAIGN_COUNT ],
				[ TransientsInterface::ADS_HIGHEST_SPEND_CAMPAIGN ]
			);

		$this->assertEquals(
			$expected,
			$this->campaign->create_campaign( $campaign_data )
		);
	}

	public function test_create_campaign_null_location_id() {
		$campaign_data = [
			'name'                                  => 'New Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'Null location' ],
			'eu_political_advertising_confirmation' => false,
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
			'name'                                  => 'Invalid Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'US', 'GB' ],
			'eu_political_advertising_confirmation' => false,
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
			'name'                                  => 'New Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'Invalid location' ],
			'eu_political_advertising_confirmation' => false,
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

		$this->transients->expects( $this->once() )
			->method( 'delete' )
			->with( TransientsInterface::ADS_HIGHEST_SPEND_CAMPAIGN );

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

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->withConsecutive(
				[ TransientsInterface::ADS_CAMPAIGN_COUNT ],
				[ TransientsInterface::ADS_HIGHEST_SPEND_CAMPAIGN ]
			);

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
			'name'                                  => 'New Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'US', 'GB' ],
			'label'                                 => 'wc-gla',
			'eu_political_advertising_confirmation' => false,
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

		$this->transients->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->withConsecutive(
				[ TransientsInterface::ADS_CAMPAIGN_COUNT ],
				[ TransientsInterface::ADS_HIGHEST_SPEND_CAMPAIGN ]
			);
		$this->campaign_label->expects( $this->once() )
			->method( 'assign_label_to_campaign_by_label_name' )
			->with( self::TEST_CAMPAIGN_ID, 'wc-gla' );

		$this->assertEquals(
			$expected,
			$this->campaign->create_campaign( $campaign_data )
		);
	}

	public function test_create_campaign_with_brand_assets() {
		$business_name_asset = [
			'field_type' => AssetFieldType::BUSINESS_NAME,
			'content'    => 'My Shop',
		];
		$logo_asset          = [
			'field_type' => AssetFieldType::LOGO,
			'content'    => 'https://example.com/logo.png',
		];
		$headline_asset      = [
			'field_type' => AssetFieldType::HEADLINE,
			'content'    => 'Test headline',
		];

		$campaign_data = [
			'name'                                  => 'New Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'US' ],
			'eu_political_advertising_confirmation' => false,
			'final_url'                             => 'https://example.com',
			'assets'                                => [ $business_name_asset, $headline_asset, $logo_asset ],
		];

		$this->wc->expects( $this->once() )
			->method( 'get_base_country' )
			->willReturn( self::BASE_COUNTRY );

		$this->asset_group->expects( $this->once() )
			->method( 'create_operations_with_assets' )
			->with(
				$this->anything(),
				'New Campaign',
				'https://example.com',
				[ $headline_asset ]
			)
			->willReturn( [] );

		$business_name_op = $this->generate_asset_create_operation( -10, AssetFieldType::BUSINESS_NAME, 'My Shop' );
		$logo_op          = $this->generate_asset_create_operation( -11, AssetFieldType::LOGO, 'https://example.com/logo.png' );

		$ads_asset = $this->createMock( AdsAsset::class );
		$ads_asset->expects( $this->once() )
			->method( 'create_operations' )
			->with( [ $business_name_asset, $logo_asset ] )
			->willReturn( [ $business_name_op, $logo_op ] );
		$this->container->addShared( AdsAsset::class, $ads_asset );

		$this->campaign_asset->expects( $this->once() )
			->method( 'create_link_operations_for_resources' )
			->with(
				$this->anything(),
				[ $business_name_op->getAssetOperation()->getCreate()->getResourceName() ],
				[ $logo_op->getAssetOperation()->getCreate()->getResourceName() ]
			)
			->willReturn( [] );

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

	public function test_create_campaign_throws_exception() {
		$campaign_data = [
			'name'                                  => 'New Campaign',
			'amount'                                => 20,
			'targeted_locations'                    => [ 'US', 'GB' ],
			'label'                                 => 'wc-gla',
			'eu_political_advertising_confirmation' => false,
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
