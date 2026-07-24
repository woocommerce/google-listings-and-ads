<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\CampaignNoSalesEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class CampaignNoSalesEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class CampaignNoSalesEvaluatorTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

	/** @var MockObject|AdsReport $ads_report */
	protected $ads_report;

	/** @var MockObject|AdsRecommendationsService $ads_recommendations */
	protected $ads_recommendations;

	/** @var CampaignNoSalesEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service         = $this->createMock( AdsService::class );
		$this->ads_campaign        = $this->createMock( AdsCampaign::class );
		$this->ads_report          = $this->createMock( AdsReport::class );
		$this->ads_recommendations = $this->createMock( AdsRecommendationsService::class );
		$this->evaluator           = new CampaignNoSalesEvaluator( $this->ads_campaign, $this->ads_report, $this->ads_recommendations );
		$this->evaluator->set_ads_object( $this->ads_service );
	}

	/**
	 * Mock no raise budget recommendations available.
	 */
	private function mock_no_raise_budget_recommendations(): void {
		$this->ads_recommendations->method( 'get_recommendations' )
			->with(
				[
					'types'       => [
						'CAMPAIGN_BUDGET',
						'MARGINAL_ROI_CAMPAIGN_BUDGET',
					],
					'campaign_id' => 0,
				]
			)
			->willReturn( [] );
	}

	public function test_get_id() {
		$this->assertEquals( 'campaign-no-sales', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::CAMPAIGN_NO_SALES, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::CAMPAIGN_NO_SALES, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_not_show_when_ads_incomplete() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->ads_recommendations->expects( $this->never() )->method( 'get_recommendations' );
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );
		$this->ads_report->expects( $this->never() )->method( 'get_report_data' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_raise_budget_recommendation_available() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_recommendations->method( 'get_recommendations' )
			->with(
				[
					'types'       => [
						'CAMPAIGN_BUDGET',
						'MARGINAL_ROI_CAMPAIGN_BUDGET',
					],
					'campaign_id' => 0,
				]
			)
			->willReturn(
				[
					[
						'id'   => 1,
						'type' => 'CAMPAIGN_BUDGET',
					],
				]
			);
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );
		$this->ads_report->expects( $this->never() )->method( 'get_report_data' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_enabled_campaign_has_zero_conversions() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->mock_no_raise_budget_recommendations();
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn(
				[
					[
						'id'     => 1,
						'status' => CampaignStatus::ENABLED,
					],
					[
						'id'     => 2,
						'status' => CampaignStatus::PAUSED,
					],
				]
			);
		$this->ads_report->method( 'get_report_data' )
			->with(
				'campaigns',
				[
					'fields' => [ 'conversions' ],
				]
			)
			->willReturn(
				[
					'campaigns' => [
						[
							'id'        => 1,
							'subtotals' => [
								'conversions' => 0,
							],
						],
					],
				]
			);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_enabled_campaigns_have_conversions() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->mock_no_raise_budget_recommendations();
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn(
				[
					[
						'id'     => 1,
						'status' => CampaignStatus::ENABLED,
					],
				]
			);
		$this->ads_report->method( 'get_report_data' )
			->willReturn(
				[
					'campaigns' => [
						[
							'id'        => 1,
							'subtotals' => [
								'conversions' => 3,
							],
						],
					],
				]
			);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_enabled_campaign_missing_from_report() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->mock_no_raise_budget_recommendations();
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn(
				[
					[
						'id'     => 1,
						'status' => CampaignStatus::ENABLED,
					],
				]
			);
		$this->ads_report->method( 'get_report_data' )->willReturn( [ 'campaigns' => [] ] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_cache_hit_skips_api_call() {
		$this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_site( 'campaign-no-sales' ), 0, HOUR_IN_SECONDS );

		$this->ads_recommendations->expects( $this->never() )->method( 'get_recommendations' );
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );
		$this->ads_report->expects( $this->never() )->method( 'get_report_data' );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
