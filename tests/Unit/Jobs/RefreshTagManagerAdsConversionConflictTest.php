<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerException;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshTagManagerAdsConversionConflict;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class RefreshTagManagerAdsConversionConflictTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class RefreshTagManagerAdsConversionConflictTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor */
	protected $monitor;

	/** @var MockObject|TagManagerConnection */
	protected $connection;

	/** @var RefreshTagManagerAdsConversionConflict */
	protected $job;

	protected const JOB_NAME     = 'refresh_tag_manager_ads_conversion_conflict';
	protected const PROCESS_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->connection       = $this->createMock( TagManagerConnection::class );

		$this->job = new RefreshTagManagerAdsConversionConflict(
			$this->action_scheduler,
			$this->monitor,
			$this->connection
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_runs_twice_a_day() {
		$this->assertEquals( 12 * HOUR_IN_SECONDS, $this->job->get_interval() );
	}

	public function test_cannot_schedule_without_a_connected_container() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'account_id' => '123' ] );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_can_schedule_with_a_connected_container() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'container_id' => '456' ] );

		$this->assertTrue( $this->job->can_schedule() );
	}

	public function test_schedule_starts_one_interval_from_now() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'container_id' => '456' ] );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_recurring' )
			->with(
				$this->callback(
					function ( $timestamp ) {
						$expected = time() + 12 * HOUR_IN_SECONDS;

						return abs( $timestamp - $expected ) <= 1;
					}
				),
				12 * HOUR_IN_SECONDS,
				self::PROCESS_HOOK,
				[]
			);

		$this->job->schedule();
	}

	public function test_schedule_does_nothing_without_a_connected_container() {
		$this->connection->method( 'get_connection_data' )->willReturn( [] );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_recurring' );

		$this->job->schedule();
	}

	public function test_unschedule_cancels_the_recurring_check() {
		$this->action_scheduler->method( 'has_scheduled_action' )
			->with( self::PROCESS_HOOK, null )
			->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'cancel' )
			->with( self::PROCESS_HOOK );

		$this->job->unschedule();
	}

	public function test_unschedule_does_nothing_when_not_scheduled() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		$this->action_scheduler->expects( $this->never() )->method( 'cancel' );

		$this->job->unschedule();
	}

	public function test_unschedule_tolerates_the_action_disappearing_before_cancel() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( true );
		$this->action_scheduler->method( 'cancel' )
			->willThrowException( ActionSchedulerException::action_not_found( self::PROCESS_HOOK ) );

		$this->job->unschedule();

		$this->addToAssertionCount( 1 );
	}

	public function test_process_items_refreshes_the_stored_result() {
		$this->connection->expects( $this->once() )
			->method( 'refresh_ads_conversion_conflict' )
			->willReturn( true );

		$this->job->process_items( [] );
	}

	public function test_process_items_keeps_the_last_result_and_logs_when_the_check_fails() {
		$failure = new Exception( 'cURL error 28: Operation timed out' );
		$this->connection->method( 'refresh_ads_conversion_conflict' )->willThrowException( $failure );

		// The connection only stores a result on success, so nothing else is written here.
		$this->connection->expects( $this->never() )->method( 'update_connection_data' );

		$logged = [];
		add_action(
			'woocommerce_gla_exception',
			function ( $exception ) use ( &$logged ) {
				$logged[] = $exception;
			}
		);

		$this->job->process_items( [] );

		$this->assertSame( [ $failure ], $logged );
	}
}
