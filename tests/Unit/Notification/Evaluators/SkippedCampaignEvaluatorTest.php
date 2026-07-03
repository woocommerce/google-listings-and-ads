<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SkippedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SkippedCampaignEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class SkippedCampaignEvaluatorTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

	/** @var SkippedCampaignEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service  = $this->createMock( AdsService::class );
		$this->ads_campaign = $this->createMock( AdsCampaign::class );
		$this->evaluator    = new SkippedCampaignEvaluator( $this->ads_campaign );
		$this->evaluator->set_ads_object( $this->ads_service );
	}

	public function test_get_id() {
		$this->assertEquals( 'skipped-campaign-creation', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::SKIPPED_CAMPAIGN_CREATION, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration_is_null() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_not_show_when_ads_incomplete() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_ads_complete_and_no_campaigns() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn( [] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_ads_complete_and_only_paused_pmax_campaign() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn(
				[
					[
						'id'     => 1,
						'type'   => CampaignType::PERFORMANCE_MAX,
						'status' => CampaignStatus::PAUSED,
					],
				]
			);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_ads_complete_and_only_non_pmax_enabled_campaign() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn(
				[
					[
						'id'     => 1,
						'type'   => CampaignType::SHOPPING,
						'status' => CampaignStatus::ENABLED,
					],
				]
			);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_enabled_pmax_campaign_present() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn(
				[
					[
						'id'     => 1,
						'type'   => CampaignType::PERFORMANCE_MAX,
						'status' => CampaignStatus::ENABLED,
					],
				]
			);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_enabled_pmax_campaign_created_outside_onboarding() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn(
				[
					[
						'id'     => 1,
						'type'   => CampaignType::SHOPPING,
						'status' => CampaignStatus::ENABLED,
					],
					[
						'id'     => 2,
						'type'   => CampaignType::PERFORMANCE_MAX,
						'status' => CampaignStatus::ENABLED,
					],
				]
			);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_cache_hit_skips_api_call() {
		$user_id = $this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_user( 'skipped-campaign-creation', $user_id ), 0, HOUR_IN_SECONDS );

		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
