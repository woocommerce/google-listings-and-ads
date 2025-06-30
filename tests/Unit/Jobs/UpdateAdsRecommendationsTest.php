<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAdsRecommendations;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateAdsRecommendationsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateAdsRecommendationsTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor */
	protected $monitor;

	/** @var MockObject|AdsRecommendationsService */
	protected $recommendations;

	/** @var MockObject|AccountService $account */
	protected $account;

	/** @var UpdateAdsRecommendations */
	protected $job;

	protected const JOB_NAME = 'update_ads_recommendations';

	protected const TEST_ACCOUNT_ID        = 1234567890;
	protected const TEST_CONNECTED_DATA    = [
		'id'       => self::TEST_ACCOUNT_ID,
		'currency' => 'EUR',
		'symbol'   => '€',
		'status'   => 'connected',
	];
	protected const TEST_DISCONNECTED_DATA = [
		'id'       => 0,
		'currency' => null,
		'symbol'   => '€',
		'status'   => 'disconnected',
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->recommendations  = $this->createMock( AdsRecommendationsService::class );
		$this->account          = $this->createMock( AccountService::class );

		$this->job = new UpdateAdsRecommendations(
			$this->action_scheduler,
			$this->monitor,
			$this->recommendations,
			$this->account
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_job_cannot_schedule_when_not_connected() {
		$this->account->expects( $this->once() )
			->method( 'get_connected_account' )
			->willReturn( self::TEST_DISCONNECTED_DATA );

			$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_job_can_schedule_when_connected() {
		$this->account->expects( $this->once() )
			->method( 'get_connected_account' )
			->willReturn( self::TEST_CONNECTED_DATA );

		$this->assertTrue( $this->job->can_schedule() );
	}

	public function test_schedule_calls_action_scheduler() {
		$this->account->expects( $this->once() )
			->method( 'get_connected_account' )
			->willReturn( self::TEST_CONNECTED_DATA );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_recurring' )
			->with(
				time(), // timestamp
				WEEK_IN_SECONDS, // interval
				'gla/jobs/' . self::JOB_NAME . '/process_item',
				[]
			);

		$this->job->schedule();
	}

	public function test_is_running_checks_action_scheduler() {
		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( 'gla/jobs/' . self::JOB_NAME . '/process_item', null )
			->willReturn( true );

		$this->assertTrue( $this->job->is_scheduled() );
	}

	public function test_process_items_get_google_recommendations() {
		$this->recommendations->expects( $this->once() )
			->method( 'get_google_recommendations' )
			->with( [] );

		$this->job->process_items( [] );
	}

	public function test_process_items_throws_exception_on_failure() {
		$this->recommendations->method( 'get_google_recommendations' )
			->willThrowException( new \Exception( 'API error' ) );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Error updating ads recommendations: API error' );

		$this->job->process_items( [] );
	}
}
