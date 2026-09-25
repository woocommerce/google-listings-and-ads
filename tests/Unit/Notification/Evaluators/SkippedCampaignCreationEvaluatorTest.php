<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\SkippedCampaignCreationEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SkippedCampaignCreationEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class SkippedCampaignCreationEvaluatorTest extends UnitTest {

	/** @var MockObject|AdsService $ads_service */
	protected $ads_service;

	/** @var MockObject|AdsCampaign $ads_campaign */
	protected $ads_campaign;

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|ServiceBasedMerchantState $service_based_merchant_state */
	protected $service_based_merchant_state;

	/** @var SkippedCampaignCreationEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_service                  = $this->createMock( AdsService::class );
		$this->ads_campaign                 = $this->createMock( AdsCampaign::class );
		$this->onboarding_completed         = $this->createMock( OnboardingCompleted::class );
		$this->options                      = $this->createMock( OptionsInterface::class );
		$this->service_based_merchant_state = $this->createMock( ServiceBasedMerchantState::class );
		$this->evaluator                    = new SkippedCampaignCreationEvaluator( $this->ads_campaign, $this->onboarding_completed, $this->service_based_merchant_state );
		$this->evaluator->set_ads_object( $this->ads_service );
		$this->evaluator->set_options_object( $this->options );
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

	public function test_get_invalidation_hooks_covers_onboarding_ads_setup_and_campaign() {
		// Finishing onboarding without a campaign turns the notification on; completing Ads
		// setup or creating a campaign turns it off. Retail (shopping) merchants complete
		// onboarding via the Merchant Center settings sync (not the onboarding_completed
		// action), so that hook must be included or a shopping merchant who skips campaign
		// creation would keep seeing the stale pre-onboarding result until the cache expires.
		$this->assertSame(
			[
				'woocommerce_gla_onboarding_completed',
				'woocommerce_gla_mc_settings_sync',
				'woocommerce_gla_ads_setup_completed',
				'woocommerce_gla_updated_campaign',
			],
			$this->evaluator->get_invalidation_hooks()
		);
	}

	public function test_should_not_show_when_onboarding_incomplete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->ads_service->expects( $this->never() )->method( 'is_setup_complete' );
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_shopping_merchant_ads_setup_complete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_service_based_merchant_ads_setup_complete_and_no_enabled_campaign() {
		// Service-based merchants always complete Ads setup during the ads-only onboarding,
		// even when they skip campaign creation. The completed Ads setup must NOT suppress the
		// notification for them; the absence of an enabled campaign should trigger it.
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->options->method( 'get_ads_id' )->willReturn( 123 );
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn( [] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_service_based_merchant_has_enabled_pmax_campaign() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( true );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( true );
		$this->options->method( 'get_ads_id' )->willReturn( 123 );
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

	public function test_should_show_when_onboarded_ads_skipped_and_no_campaigns() {
		$this->mock_onboarded_with_ads_skipped();
		$this->ads_campaign->method( 'get_campaigns' )
			->with( true, false )
			->willReturn( [] );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_only_non_pmax_campaign_present() {
		$this->mock_onboarded_with_ads_skipped();
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
		$this->mock_onboarded_with_ads_skipped();
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

	public function test_should_show_when_only_paused_pmax_campaign_present() {
		$this->mock_onboarded_with_ads_skipped();
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

	public function test_should_not_show_when_pmax_campaign_created_outside_onboarding() {
		$this->mock_onboarded_with_ads_skipped();
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

	public function test_should_not_show_when_get_campaigns_throws() {
		$this->mock_onboarded_with_ads_skipped();
		$this->ads_campaign->method( 'get_campaigns' )
			->willThrowException( new ExceptionWithResponseData( 'error' ) );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_cache_hit_skips_api_call() {
		$this->login_as_administrator();

		set_transient( NotificationCacheKeys::for_site( 'skipped-campaign-creation' ), 0, HOUR_IN_SECONDS );

		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_no_ads_account_connected() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->options->method( 'get_ads_id' )->willReturn( 0 );
		$this->ads_campaign->expects( $this->never() )->method( 'get_campaigns' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	/**
	 * Mock a shopping merchant that finished onboarding, did not complete Ads setup, but has
	 * a connected Ads account ID (so the campaign query is reached).
	 */
	private function mock_onboarded_with_ads_skipped(): void {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->service_based_merchant_state->method( 'is_service_based_merchant' )->willReturn( false );
		$this->ads_service->method( 'is_setup_complete' )->willReturn( false );
		$this->options->method( 'get_ads_id' )->willReturn( 123 );
	}
}
