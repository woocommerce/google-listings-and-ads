<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteLocalPost;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Error;
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
		$this->local_post_service->method( 'should_remove_from_business_profile' )->willReturn( true );

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

	/**
	 * The race this guards against: sharing was switched back on between scheduling the removal
	 * and this job running, so the post is genuinely still meant to stay shared.
	 */
	public function test_process_items_skips_a_post_that_should_no_longer_be_removed() {
		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->local_post_service->method( 'should_remove_from_business_profile' )->willReturn( false );
		$this->local_post_service->expects( $this->never() )->method( 'delete' );
		$this->job = new DeleteLocalPost( $this->action_scheduler, $this->monitor, $this->local_post_service );

		$this->job->process_items( [ 123, $this->result ] );
	}

	public function test_process_items_clears_a_stale_outcome_from_an_earlier_failed_attempt_on_success() {
		$this->local_post_service->expects( $this->once() )->method( 'clear_sync_outcome' )->with( 123 );

		$this->job->process_items( [ 123, $this->result ] );
	}

	public function test_process_items_records_a_failed_removal_of_a_published_post_instead_of_letting_it_propagate() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post_id, SyncOutcome::UNSHARE_FAILURE, '' );
		$this->local_post_service->expects( $this->never() )->method( 'clear_sync_outcome' );

		$this->job->process_items( [ $post_id, $this->result ] );
	}

	/**
	 * A lost Google grant needs reconnecting, not just a resave -- it must stay distinct from the
	 * generic `UNSHARE_FAILURE` outcome so the editor can tell the merchant the right thing to do.
	 */
	public function test_process_items_records_an_account_disconnected_outcome_for_a_lost_google_grant() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->local_post_service->method( 'delete' )->willThrowException( AccountReconnect::google_disconnected() );
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post_id, SyncOutcome::UNSHARE_ACCOUNT_DISCONNECTED, '' );

		$this->job->process_items( [ $post_id, $this->result ] );
	}

	/**
	 * LocalPostService::delete() already reports an Exception before rethrowing it, so reporting
	 * it here too would log every failed removal twice.
	 */
	public function test_process_items_does_not_report_a_failed_removals_exception_a_second_time() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );

		$reported = 0;
		add_action(
			'woocommerce_gla_exception',
			function () use ( &$reported ) {
				++$reported;
			}
		);

		$this->job->process_items( [ $post_id, $this->result ] );

		$this->assertSame( 0, $reported );
	}

	public function test_process_items_reports_a_failed_removals_error_by_message() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->local_post_service->method( 'delete' )->willThrowException( new Error( 'Type error.' ) );

		$logged = [];
		add_action(
			'woocommerce_gla_error',
			function ( string $message ) use ( &$logged ) {
				$logged[] = $message;
			}
		);

		$this->job->process_items( [ $post_id, $this->result ] );

		$this->assertSame( [ 'Type error.' ], $logged );
	}

	public function test_a_throwing_error_listener_in_record_failure_does_not_break_processing() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->local_post_service->method( 'delete' )->willThrowException( new Error( 'Type error.' ) );
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post_id, SyncOutcome::UNSHARE_FAILURE, $this->anything() );

		$logged = [];
		add_action(
			'woocommerce_gla_error',
			function ( string $message ) use ( &$logged ) {
				if ( 'Type error.' === $message ) {
					throw new Exception( 'Listener failed.' );
				}
				$logged[] = $message;
			}
		);

		$this->job->process_items( [ $post_id, $this->result ] );

		$this->assertSame( [ 'A woocommerce_gla_error callback threw: Listener failed.' ], $logged );
	}

	public function test_handle_process_items_action_does_not_reschedule_a_failed_removal_of_a_published_post() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->handle_process_items_action( [ $post_id, $this->result ] );
	}

	public function test_a_failed_removal_of_a_trashed_post_propagates_without_recording_an_outcome() {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'trash' ] );
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->local_post_service->expects( $this->never() )->method( 'set_sync_outcome' );

		$this->expectException( Exception::class );

		$this->job->process_items( [ $post_id, $this->result ] );
	}

	public function test_a_failed_removal_of_a_permanently_deleted_post_propagates_without_recording_an_outcome() {
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->local_post_service->expects( $this->never() )->method( 'set_sync_outcome' );

		$this->expectException( Exception::class );

		$this->job->process_items( [ PHP_INT_MAX, $this->result ] );
	}

	public function test_handle_process_items_action_reschedules_and_rethrows_on_a_delete_failure() {
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->action_scheduler->expects( $this->once() )->method( 'schedule_immediate' );

		$this->expectException( Exception::class );

		$this->job->handle_process_items_action( [ PHP_INT_MAX, $this->result ] );
	}
}
