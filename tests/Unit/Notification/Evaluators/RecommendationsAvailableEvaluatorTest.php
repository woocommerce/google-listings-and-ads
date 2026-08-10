<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\RecommendationsAvailableEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class RecommendationsAvailableEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class RecommendationsAvailableEvaluatorTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|AdsRecommendationsService $ads_recommendations */
	protected $ads_recommendations;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

	/** @var RecommendationsAvailableEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service         = $this->createMock( AdsService::class );
		$this->ads_recommendations = $this->createMock( AdsRecommendationsService::class );
		$this->ads_campaign        = $this->createMock( AdsCampaign::class );
		$this->evaluator           = new RecommendationsAvailableEvaluator( $this->ads_recommendations, $this->ads_campaign );
		$this->evaluator->set_ads_object( $this->ads_service );
	}

	public function test_get_id() {
		$this->assertEquals( 'recommendations-available', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::RECOMMENDATIONS_AVAILABLE, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::RECOMMENDATIONS_AVAILABLE, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_not_show_when_ads_incomplete() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->ads_recommendations->expects( $this->never() )->method( 'get_recommendations' );
		$this->ads_campaign->expects( $this->never() )->method( 'get_highest_spend_campaign' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_budget_recommendations_present() {
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
		$this->ads_campaign->expects( $this->never() )->method( 'get_highest_spend_campaign' );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_pmax_recommendations_present() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_recommendations->method( 'get_recommendations' )
			->willReturnCallback(
				function ( array $args ) {
					if ( 0 === $args['campaign_id'] ) {
						return [];
					}

					return [
						[
							'id'   => 2,
							'type' => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
						],
					];
				}
			);
		$this->ads_campaign->method( 'get_highest_spend_campaign' )
			->willReturn(
				[
					'id' => 1234567890,
				]
			);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_recommendations() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_recommendations->method( 'get_recommendations' )->willReturn( [] );
		$this->ads_campaign->method( 'get_highest_spend_campaign' )->willReturn( [] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_cache_hit_skips_api_call() {
		$this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_site( 'recommendations-available' ), 0, HOUR_IN_SECONDS );

		$this->ads_recommendations->expects( $this->never() )->method( 'get_recommendations' );
		$this->ads_campaign->expects( $this->never() )->method( 'get_highest_spend_campaign' );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
