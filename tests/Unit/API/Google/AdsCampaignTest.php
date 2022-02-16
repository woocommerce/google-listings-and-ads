<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignBudget;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Google\ApiCore\ApiException;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|AdsGroup          $ad_group
 * @property MockObject|AdsAssetGroup     $asset_group
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
		$this->asset_group = $this->createMock( AdsAssetGroup::class );
		$this->budget      = $this->createMock( AdsCampaignBudget::class );
		$this->options     = $this->createMock( OptionsInterface::class );

		$this->container = new Container();
		$this->container->share( AdsGroup::class, $this->ad_group );
		$this->container->share( AdsAssetGroup::class, $this->asset_group );

		$this->campaign = new AdsCampaign( $this->client, $this->budget );
		$this->campaign->set_options_object( $this->options );
		$this->campaign->set_container( $this->container );

		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );
	}

	public function test_get_campaigns_empty_list() {
		$this->generate_ads_campaign_query_mock( [] );
		$this->assertEquals( [], $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns() {
		$campaigns_data = [
			[
				'id'      => self::TEST_CAMPAIGN_ID,
				'name'    => 'Campaign One',
				'status'  => 'paused',
				'type'    => 'shopping',
				'amount'  => 10,
				'country' => 'US',
			],
			[
				'id'      => 5678901234,
				'name'    => 'Campaign Two',
				'status'  => 'enabled',
				'type'    => 'performance_max',
				'amount'  => 20,
				'country' => 'UK',
			],
		];

		$this->generate_ads_campaign_query_mock( $campaigns_data );
		$this->assertEquals( $campaigns_data, $this->campaign->get_campaigns() );
	}

	public function test_get_campaigns_exception() {
		$this->generate_ads_query_mock_exception( new ApiException( 'unavailable', 14, 'UNAVAILABLE' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error retrieving campaigns: unavailable' );
		$this->expectExceptionCode( 503 );

		$this->campaign->get_campaigns();
	}

	public function test_get_campaign() {
		$campaign_data = [
			'id'      => self::TEST_CAMPAIGN_ID,
			'name'    => 'Single Campaign',
			'status'  => 'enabled',
			'type'    => 'performance_max',
			'amount'  => 10,
			'country' => 'US',
		];

		$this->generate_ads_campaign_query_mock( [ $campaign_data ] );
		$this->assertEquals( $campaign_data, $this->campaign->get_campaign( self::TEST_CAMPAIGN_ID ) );
	}

	public function test_get_campaign_exception() {
		$this->generate_ads_query_mock_exception( new ApiException( 'not found', 5, 'NOT_FOUND' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error retrieving campaign: not found' );
		$this->expectExceptionCode( 404 );

		$this->campaign->get_campaign( self::TEST_CAMPAIGN_ID );
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
			'type'   => 'performance_max',
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
				],
			],
		];

		$this->generate_campaign_mutate_mock_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT', [ 'metadata' => [ $errors ] ] )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'A campaign with this name already exists' );
		$this->expectExceptionCode( 400 );

		$this->campaign->create_campaign( $campaign_data );
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

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error editing campaign: invalid' );
		$this->expectExceptionCode( 400 );

		$this->campaign->edit_campaign( self::TEST_CAMPAIGN_ID, $campaign_data );
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
				],
			],
		];

		$this->generate_campaign_mutate_mock_exception(
			new ApiException( 'invalid', 3, 'INVALID_ARGUMENT', [ 'metadata' => [ $errors ] ] )
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'This campaign has already been deleted' );
		$this->expectExceptionCode( 400 );

		$this->campaign->delete_campaign( self::TEST_CAMPAIGN_ID );
	}

}
