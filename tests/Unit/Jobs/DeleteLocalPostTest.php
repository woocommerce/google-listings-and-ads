<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteLocalPost;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteLocalPostTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class DeleteLocalPostTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var DeleteLocalPost $job */
	protected $job;

	/**
	 * The stored result a captured delete passes through unchanged.
	 *
	 * @var array
	 */
	protected $result = [
		'account_id'     => '111',
		'location_id'    => '222',
		'google_post_id' => '333',
		'search_url'     => 'https://g.page/r/abc',
		'wp_post_id'     => 123,
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->local_post_service = $this->createMock( LocalPostService::class );

		$this->job = new DeleteLocalPost( $this->action_scheduler, $this->monitor, $this->local_post_service );
	}

	public function test_schedule_throws_when_no_post_id_is_provided() {
		$this->expectException( JobException::class );

		$this->job->schedule( [ null, $this->result ] );
	}

	public function test_schedule_throws_when_no_result_is_provided() {
		$this->expectException( JobException::class );

		$this->job->schedule( [ 123 ] );
	}

	public function test_schedule_does_not_reschedule_a_post_already_queued() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( true );
		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule( [ 123, $this->result ] );
	}

	public function test_schedule_schedules_a_post_not_already_queued() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( $this->anything(), [ [ 123, $this->result ] ] );

		$this->job->schedule( [ 123, $this->result ] );
	}

	public function test_schedule_checks_for_a_duplicate_with_the_same_args_it_schedules_with() {
		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( $this->anything(), [ [ 123, $this->result ] ] )
			->willReturn( false );

		$this->job->schedule( [ 123, $this->result ] );
	}

	public function test_process_items_deletes_the_post_with_its_stored_result() {
		$this->local_post_service->expects( $this->once() )->method( 'delete' )->with( 123, $this->result );

		$this->job->process_items( [ 123, $this->result ] );
	}

	public function test_handle_process_items_action_calls_through_and_detaches_the_timeout_monitor() {
		$this->monitor->expects( $this->once() )->method( 'validate_failure_rate' );
		$this->monitor->expects( $this->once() )->method( 'attach_timeout_monitor' );
		$this->monitor->expects( $this->once() )->method( 'detach_timeout_monitor' );
		$this->local_post_service->expects( $this->once() )->method( 'delete' )->with( 123, $this->result );

		$this->job->handle_process_items_action( [ 123, $this->result ] );
	}

	public function test_a_delete_failure_propagates_uncaught_so_actionscheduler_can_reschedule_it() {
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );

		$this->expectException( Exception::class );

		$this->job->process_items( [ 123, $this->result ] );
	}

	public function test_handle_process_items_action_reschedules_and_rethrows_on_a_delete_failure() {
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->action_scheduler->expects( $this->once() )->method( 'schedule_immediate' );

		$this->expectException( Exception::class );

		$this->job->handle_process_items_action( [ 123, $this->result ] );
	}
}
