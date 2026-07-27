<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\PausedCampaignEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class PausedCampaignEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class PausedCampaignEvaluatorTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

	/** @var PausedCampaignEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service  = $this->createMock( AdsService::class );
		$this->ads_campaign = $this->createMock( AdsCampaign::class );
		$this->evaluator    = new PausedCampaignEvaluator( $this->ads_campaign );
		$this->evaluator->set_ads_object( $this->ads_service );
	}

	public function test_get_id() {
		$this->assertEquals( 'paused-campaign', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::PAUSED_CAMPAIGN, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::PAUSED_CAMPAIGN, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_not_show_when_ads_incomplete() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_campaigns() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn( [] );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_paused_campaign_present() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
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

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_paused_campaigns() {
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
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

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_cache_hit_skips_api_call() {
		$this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_site( 'paused-campaign' ), 0, HOUR_IN_SECONDS );

		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
