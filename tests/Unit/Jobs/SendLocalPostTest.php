<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\MissingLocalPostIdException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\SendLocalPost;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\WPHookTrait;
use Error;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class SendLocalPostTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class SendLocalPostTest extends UnitTest {

	use WPHookTrait;

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var SendLocalPost $job */
	protected $job;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->location_service   = $this->createMock( LocationService::class );
		$this->location_service->method( 'get_selected_location' )->willReturn( [ 'title' => 'Jane\'s Bakery' ] );

		// Not shared by default -- the one test needing otherwise overrides this itself.
		$this->local_post_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => false,
				'search_url' => '',
			]
		);

		$this->job = new SendLocalPost( $this->action_scheduler, $this->monitor, $this->local_post_service, $this->location_service );
	}

	public function test_schedule_throws_when_no_post_id_is_provided() {
		$this->expectException( JobException::class );

		$this->job->schedule();
	}

	public function test_schedule_does_not_reschedule_a_post_already_queued() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( true );
		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule( [ [ 123 ] ] );
	}

	public function test_schedule_schedules_a_post_not_already_queued() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( $this->anything(), [ [ 123 ] ] );

		$this->job->schedule( [ [ 123 ] ] );
	}

	public function test_a_qualifying_post_is_sent_and_stores_success() {
		$post = $this->create_shared_post();

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_a_post_whose_sharing_toggle_is_now_off_is_skipped_without_recording_a_failure() {
		$post = $this->create_shared_post();
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, false );

		$this->local_post_service->expects( $this->never() )->method( 'create' );
		$this->local_post_service->expects( $this->never() )->method( 'set_sync_outcome' );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_a_trashed_post_is_skipped_without_recording_a_failure() {
		$post = $this->create_shared_post();
		wp_trash_post( $post->ID );

		$this->local_post_service->expects( $this->never() )->method( 'create' );
		$this->local_post_service->expects( $this->never() )->method( 'set_sync_outcome' );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_a_post_already_sent_by_another_path_is_skipped_without_recording_a_failure() {
		$post = $this->create_shared_post();

		// A second method()->willReturn() on the same mock method doesn't override the one
		// already set in setUp() -- PHPUnit keeps whichever was configured first -- so this needs
		// its own mock, stubbed as already-shared from the start, rather than re-stubbing the
		// shared one.
		$local_post_service = $this->createMock( LocalPostService::class );
		$local_post_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			]
		);
		$local_post_service->expects( $this->never() )->method( 'create' );
		$local_post_service->expects( $this->never() )->method( 'set_sync_outcome' );

		$job = new SendLocalPost( $this->action_scheduler, $this->monitor, $local_post_service, $this->location_service );
		$job->process_items( [ $post->ID ] );
	}

	public function test_a_post_whose_location_is_no_longer_connected_is_skipped_without_recording_a_failure() {
		$post                   = $this->create_shared_post();
		$this->location_service = $this->createMock( LocationService::class );
		$this->location_service->method( 'get_selected_location' )->willReturn( null );
		$this->job = new SendLocalPost( $this->action_scheduler, $this->monitor, $this->local_post_service, $this->location_service );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_a_deleted_post_is_skipped_without_recording_a_failure() {
		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->job->process_items( [ 999999 ] );
	}

	public function test_stores_success_when_google_accepts_the_post_without_returning_its_id() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502 )
		);
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::SUCCESS, '', false );

		$before = did_action( 'woocommerce_gla_exception' );

		$this->job->process_items( [ $post->ID ] );

		$this->assertSame( $before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_stores_pending_verification_when_google_accepts_a_post_for_an_unverified_location_without_returning_its_id() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502, false, SyncOutcome::SENT_PENDING_VERIFICATION )
		);
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::SENT_PENDING_VERIFICATION, '', false );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_keeps_the_omitted_image_flag_when_google_accepts_the_post_without_returning_its_id() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502, true )
		);
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::SUCCESS, '', true );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_reports_a_create_sync_success_when_google_accepts_the_post_without_returning_its_id() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502 )
		);

		$this->expect_do_action_once( 'woocommerce_gla_business_profile_sync_success', [ [ 'type' => 'create' ] ] );
		$this->expect_do_action_never( 'woocommerce_gla_business_profile_sync_failure' );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_stores_the_classified_outcome_and_googles_message_when_the_send_fails() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException(
			new BusinessProfileApiException(
				400,
				[
					'error' => [
						'code'    => 400,
						'message' => 'Request contains an invalid argument.',
						'status'  => 'INVALID_ARGUMENT',
					],
				],
				__METHOD__
			)
		);

		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::CONTENT_REJECTION, 'Request contains an invalid argument.' );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_reports_a_create_sync_success_once_google_accepts_the_post() {
		$post = $this->create_shared_post();

		$this->expect_do_action_once( 'woocommerce_gla_business_profile_sync_success', [ [ 'type' => 'create' ] ] );
		$this->expect_do_action_never( 'woocommerce_gla_business_profile_sync_failure' );

		$this->job->process_items( [ $post->ID ] );
	}

	public function test_reports_a_create_sync_failure_with_googles_error_status() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException(
			new BusinessProfileApiException(
				400,
				[
					'error' => [
						'code'    => 400,
						'message' => 'Request contains an invalid argument.',
						'status'  => 'INVALID_ARGUMENT',
					],
				],
				__METHOD__
			)
		);

		$this->expect_do_action_once(
			'woocommerce_gla_business_profile_sync_failure',
			[
				[
					'type'   => 'create',
					'reason' => 'INVALID_ARGUMENT',
				],
			]
		);
		$this->expect_do_action_never( 'woocommerce_gla_business_profile_sync_success' );

		$this->job->process_items( [ $post->ID ] );
	}

	/**
	 * @dataProvider data_provider_failures_without_a_google_error
	 *
	 * @param callable $create_failure
	 */
	public function test_reports_a_sync_failure_with_an_empty_reason_when_google_gave_no_error( callable $create_failure ) {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException( $create_failure() );

		$this->expect_do_action_once(
			'woocommerce_gla_business_profile_sync_failure',
			[
				[
					'type'   => 'create',
					'reason' => '',
				],
			]
		);

		$this->job->process_items( [ $post->ID ] );
	}

	/**
	 * @return array
	 */
	public function data_provider_failures_without_a_google_error(): array {
		return [
			'network failure' => [
				function () {
					return new BusinessProfileApiException( 503, [], __METHOD__ );
				},
			],
			'proxy error'     => [
				function () {
					return new BusinessProfileApiException( 404, [ 'message' => 'Not found' ], __METHOD__ );
				},
			],
			'PHP error'       => [
				function () {
					return new Error( 'Call to a member function on null.' );
				},
			],
		];
	}

	public function test_does_not_propagate_an_exception_from_the_create_call_and_reports_it() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException( new Exception( 'Network error.' ) );

		$before = did_action( 'woocommerce_gla_exception' );

		$this->job->process_items( [ $post->ID ] );

		$this->assertSame( $before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_does_not_propagate_an_error_from_the_create_call_and_reports_it_by_message() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException( new Error( 'Call to a member function on null.' ) );
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::REQUEST_FAILURE, '' );

		$exceptions_before = did_action( 'woocommerce_gla_exception' );
		$errors_before     = did_action( 'woocommerce_gla_error' );

		$this->job->process_items( [ $post->ID ] );

		$this->assertSame( $exceptions_before, did_action( 'woocommerce_gla_exception' ) );
		$this->assertSame( $errors_before + 1, did_action( 'woocommerce_gla_error' ) );
	}

	public function test_a_throwing_sync_failure_listener_does_not_break_processing_or_skip_the_failure_report() {
		$post = $this->create_shared_post();
		$this->local_post_service->method( 'create' )->willThrowException(
			new BusinessProfileApiException( 503, [], __METHOD__ )
		);

		add_action(
			'woocommerce_gla_business_profile_sync_failure',
			function () {
				throw new Exception( 'Listener failed.' );
			}
		);

		$logged = [];
		add_action(
			'woocommerce_gla_error',
			function ( string $message ) use ( &$logged ) {
				$logged[] = $message;
			}
		);
		$exceptions_before = did_action( 'woocommerce_gla_exception' );

		$this->job->process_items( [ $post->ID ] );

		$this->assertSame( [ 'A woocommerce_gla_business_profile_sync_failure callback threw: Listener failed.' ], $logged );
		$this->assertSame( $exceptions_before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_a_throwing_sync_success_listener_does_not_break_processing_or_store_a_failure() {
		$post = $this->create_shared_post();

		add_action(
			'woocommerce_gla_business_profile_sync_success',
			function () {
				throw new Exception( 'Listener failed.' );
			}
		);

		$this->local_post_service->expects( $this->never() )->method( 'set_sync_outcome' );

		$logged = [];
		add_action(
			'woocommerce_gla_error',
			function ( string $message ) use ( &$logged ) {
				$logged[] = $message;
			}
		);

		$this->job->process_items( [ $post->ID ] );

		$this->assertSame( [ 'A woocommerce_gla_business_profile_sync_success callback threw: Listener failed.' ], $logged );
	}

	/**
	 * Create a post of a supported type with the sharing toggle switched on.
	 */
	private function create_shared_post() {
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );

		return $post;
	}
}
