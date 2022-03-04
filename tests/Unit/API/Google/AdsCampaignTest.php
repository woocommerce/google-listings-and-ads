<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignBudget;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|AdsGroup          $ad_group
 * @property MockObject|AdsCampaignBudget $budget
 * @property MockObject|OptionsInterface  $options
 * @property AdsCampaign                  $campaign
 * @property Container                    $container
 */
class AdsCampaignTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_CAMPAIGN_ID = 1234567890;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->ads_client_setup();

		$this->ad_group    = $this->createMock( AdsGroup::class );
		$this->budget      = $this->createMock( AdsCampaignBudget::class );
		$this->options     = $this->createMock( OptionsInterface::class );

		$this->container = new Container();
		$this->container->share( AdsGroup::class, $this->ad_group );

		$this->campaign = new AdsCampaign( $this->client, $this->budget );
		$this->campaign->set_options_object( $this->options );
		$this->campaign->set_container( $this->container );

		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );
	}

	public function test_get_campaigns_empty_list() {
		$this->generate_ads_campaign_query_mock( [], [] );
		$this->assertEquals( [], $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns() {
		$campaign_criterion_data = [
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => 'geoTargetConstants/2158',
			],
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => '',
			],
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => null,
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/2344',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => '',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/2826',
			],
			[
				'campaign_id'         => 8888877777,
				'geo_target_constant' => '',
			],
		];

		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Campaign One',
				'status'  => 'paused',
				'amount'  => 10,
				'country' => 'US',
				'targeted_locations' => ['geoTargetConstants/2158'],
			],
			[
				'id'      => 5678901234,
				'name'    => 'Campaign Two',
				'status'  => 'enabled',
				'amount'  => 20,
				'country' => 'UK',
				'targeted_locations' => ['geoTargetConstants/2344', 'geoTargetConstants/2826'],
			],
			[
				'id'      => 8888877777,
				'name'    => 'Campaign Three',
				'status'  => 'enabled',
				'amount'  => 30,
				'country' => 'TW',
				'targeted_locations' => [],
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, $campaign_criterion_data );
		$this->assertEquals( $campaigns_data, $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns_with_campaign_id_of_criterion_not_found_in_campaigns() {
		$campaign_criterion_data = [
			[
				'campaign_id'         => self::TEST_CAMPAIGN_ID,
				'geo_target_constant' => 'geoTargetConstants/2158',
			],
			[
				'campaign_id'         => 5678901234,
				'geo_target_constant' => 'geoTargetConstants/2344',
			],
		];

		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Campaign One',
				'status'  => 'paused',
				'amount'  => 10,
				'country' => 'US',
				'targeted_locations' => ['geoTargetConstants/2158'],
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data, $campaign_criterion_data );
		$this->assertEquals( $campaigns_data, $this->campaign->get_campaigns() );
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
			'geo_target_constant' => '',
		];

		$campaign_data = [
			'id'      => self::TEST_CAMPAIGN_ID,
			'name'    => 'Single Campaign',
			'status'  => 'enabled',
			'amount'  => 10,
			'country' => 'US',
			'targeted_locations' => [],
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
			'name'    => 'New Campaign',
			'amount'  => 20,
			'country' => 'US',
		];

		$this->generate_campaign_mutate_mock( 'create', self::TEST_CAMPAIGN_ID );

		$expected = [
			'id'     => self::TEST_CAMPAIGN_ID,
			'status' => 'enabled',
		] + $campaign_data;

		$this->assertEquals(
			$expected,
			$this->campaign->create_campaign( $campaign_data )
		);
	}

	public function test_create_campaign_exception() {
		$campaign_data = [
			'name'    => 'Invalid Campaign',
			'amount'  => 20,
			'country' => 'US',
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

	public function test_edit_campaign() {
		$campaign_data = [
			'amount'  => 40,
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
						'INVALID_ARGUMENT'                             => 'invalid',
					],
					'id'      => self::TEST_CAMPAIGN_ID,
				],
				$e->get_response_data( true )
			);
			$this->assertEquals( 400, $e->getCode() );
		}
	}
}
