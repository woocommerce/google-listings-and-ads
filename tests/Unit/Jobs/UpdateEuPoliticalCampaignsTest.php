<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateEuPoliticalCampaigns;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateEuPoliticalCampaignsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateEuPoliticalCampaignsTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|GoogleAdsClient */
	protected $client;

	/** @var MockObject|AdsCampaign */
	protected $ads_campaign;

	/** @var MockObject|AdsService */
	protected $ads_service;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var UpdateEuPoliticalCampaigns $job */
	protected $job;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->ads_campaign     = $this->createMock( AdsCampaign::class );
		$this->ads_service      = $this->createMock( AdsService::class );
		$this->options          = $this->createMock( OptionsInterface::class );

		$this->job = new UpdateEuPoliticalCampaigns(
			$this->action_scheduler,
			$this->monitor,
			$this->ads_campaign,
			$this->ads_service,
		);

		$this->job->set_options_object( $this->options );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	public function test_can_schedule_when_ads_connected() {
		$this->ads_service->method( 'is_connected' )->willReturn( true );
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		$this->assertTrue( $this->job->can_schedule() );
	}

	public function test_cannot_schedule_when_ads_not_connected() {
		$this->ads_service->method( 'is_connected' )->willReturn( false );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_cannot_schedule_when_already_running() {
		$this->ads_service->method( 'is_connected' )->willReturn( true );
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( true );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_cannot_schedule_when_declarations_complete_flag_is_set() {
		$this->options->method( 'get' )
			->with( OptionsInterface::ADS_EU_POLITICAL_DECLARATIONS_COMPLETE )
			->willReturn( true );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_updates_campaigns_not_targeting_eu_counties() {
		$campaigns = [
			[
				'id'                 => 1,
				'targeted_locations' => [ 'US' ],
			],
			[
				'id'                 => 2,
				'targeted_locations' => [ 'US' ],
			],
		];

		$this->ads_campaign->method( 'get_campaigns_missing_eu_political_declaration' )
			->willReturn( $campaigns );

		$batch = $this->job->get_batch( 1 );

		$this->ads_campaign->method( 'get_campaigns_by_ids' )
			->with( array_column( $campaigns, 'id' ) )
			->willReturn( $campaigns );

		$this->ads_campaign->expects( $this->once() )
			->method( 'set_eu_political_campaigns' )
			->with(
				[
					[
						'id'    => 1,
						'value' => false,
					],
					[
						'id'    => 2,
						'value' => false,
					],
				]
			);

		$method = new \ReflectionMethod( UpdateEuPoliticalCampaigns::class, 'process_items' );
		$method->setAccessible( true );
		$method->invoke( $this->job, $batch );
	}

	public function test_does_not_update_campaigns_targeting_eu_counties() {
		$campaigns = [
			[
				'id'                 => 1,
				'targeted_locations' => [ 'GB' ],
			],
			[
				'id'                 => 2,
				'targeted_locations' => [ 'FR' ],
			],
			[
				'id'                 => 3,
				'targeted_locations' => [ 'US' ],
			],
		];

		$this->ads_campaign->method( 'get_campaigns_missing_eu_political_declaration' )
			->willReturn( $campaigns );

		$batch = $this->job->get_batch( 1 );

		$this->ads_campaign->method( 'get_campaigns_by_ids' )
			->with( array_column( $campaigns, 'id' ) )
			->willReturn( $campaigns );

		$this->ads_campaign->expects( $this->once() )
			->method( 'set_eu_political_campaigns' )
			->with(
				[
					[
						'id'    => 3,
						'value' => false,
					],
				]
			);

		$method = new \ReflectionMethod( UpdateEuPoliticalCampaigns::class, 'process_items' );
		$method->setAccessible( true );
		$method->invoke( $this->job, $batch );
	}
}
