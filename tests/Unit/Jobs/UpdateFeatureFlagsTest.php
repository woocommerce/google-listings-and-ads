<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WCS\ConnectionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateFeatureFlags;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateFeatureFlagsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateFeatureFlagsTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor */
	protected $monitor;

	/** @var MockObject|ConnectionService $connection */
	protected $connection;

	/** @var UpdateFeatureFlags */
	protected $job;

	protected const JOB_NAME = 'update_wcs_feature_flags';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->connection       = $this->createMock( ConnectionService::class );

		$this->job = new UpdateFeatureFlags(
			$this->action_scheduler,
			$this->monitor,
			$this->connection
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_calls_action_scheduler() {
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_recurring' )
			->with(
				time(), // timestamp
				HOUR_IN_SECONDS, // interval
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

	public function test_process_items_update_feature_flags() {
		$this->connection->expects( $this->once() )
			->method( 'update_feature_flags' );

		$this->job->process_items( [] );
	}

	public function test_process_items_throws_exception_on_failure() {
		$this->connection->method( 'update_feature_flags' )
			->willThrowException( new \Exception( 'API error' ) );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Error updating WCS feature flags: API error' );

		$this->job->process_items( [] );
	}
}
