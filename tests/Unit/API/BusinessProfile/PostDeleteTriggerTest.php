<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostImageService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostDeleteTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostPublishTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteLocalPost;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostDeleteTriggerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class PostDeleteTriggerTest extends UnitTest {

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var MockObject|DeleteLocalPost $delete_local_post_job */
	protected $delete_local_post_job;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var PostDeleteTrigger $trigger */
	protected $trigger;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		// Detach the real, globally-bootstrapped PostDeleteTrigger's own callbacks so firing
		// these hooks runs only what this test's own instance adds.
		remove_all_actions( 'wp_trash_post' );
		remove_all_actions( 'before_delete_post' );
		remove_all_actions( 'trashed_post' );
		remove_all_actions( 'deleted_post' );
		remove_all_actions( 'untrash_post' );

		// A partial mock -- get_stored_result() runs for real (it only reads post meta, no
		// constructor dependencies needed), so it reflects whatever's actually stored instead of
		// a hand-copied stand-in that could drift from the real implementation.
		$this->local_post_service = $this->getMockBuilder( LocalPostService::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'delete', 'create' ] )
			->getMock();

		$this->delete_local_post_job = $this->createMock( DeleteLocalPost::class );
		$this->job_repository        = $this->createMock( JobRepository::class );
		$this->job_repository->method( 'get' )->with( DeleteLocalPost::class )->willReturn( $this->delete_local_post_job );

		$this->trigger = new PostDeleteTrigger( $this->local_post_service, $this->job_repository );
		$this->trigger->register();
	}

	public function test_trashing_a_synced_post_schedules_a_delete() {
		$post = $this->create_synced_post();

		$this->delete_local_post_job->expects( $this->once() )->method( 'schedule' )->with( [ $post->ID, $this->stored_result( $post->ID ) ] );

		wp_trash_post( $post->ID );
	}

	public function test_permanently_deleting_a_synced_post_schedules_a_delete_without_a_prior_trash() {
		$post = $this->create_synced_post();

		// A permanent delete clears the post's own meta rows before deleted_post fires -- if the
		// trigger re-read meta there instead of using what it captured earlier, get_stored_result()
		// would come back incomplete and this would never be called with a complete result.
		$this->delete_local_post_job->expects( $this->once() )->method( 'schedule' )->with( [ $post->ID, $this->stored_result( $post->ID ) ] );

		wp_delete_post( $post->ID, true );
	}

	public function test_does_not_schedule_a_delete_for_a_post_with_an_incomplete_stored_result() {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'location_id'    => '222',
				'google_post_id' => '333',
			]
		);

		$this->delete_local_post_job->expects( $this->never() )->method( 'schedule' );

		wp_trash_post( $post->ID );
	}

	public function test_does_not_schedule_a_delete_for_an_unsynced_post() {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );

		$this->delete_local_post_job->expects( $this->never() )->method( 'schedule' );

		wp_trash_post( $post->ID );
	}

	public function test_does_not_schedule_a_delete_for_a_post_of_an_unsupported_post_type() {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		update_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, $this->stored_result( $post->ID ) );

		$this->delete_local_post_job->expects( $this->never() )->method( 'schedule' );

		wp_trash_post( $post->ID );
	}

	public function test_restoring_a_trashed_post_whose_delete_succeeded_resets_the_sharing_toggle() {
		$local_post_service = $this->local_post_service_with_a_successful_delete();
		$this->register_only( new PostDeleteTrigger( $local_post_service, $this->job_repository ) );

		$post_id = $this->create_synced_post()->ID;
		wp_trash_post( $post_id );
		$this->run_scheduled_delete( $post_id, $this->stored_result( $post_id ), $local_post_service );

		wp_untrash_post( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true ) );
	}

	public function test_restoring_a_trashed_post_whose_delete_succeeded_clears_its_last_sync_outcome() {
		$local_post_service = $this->local_post_service_with_a_successful_delete();
		$this->register_only( new PostDeleteTrigger( $local_post_service, $this->job_repository ) );

		$post = $this->create_synced_post();
		update_post_meta( $post->ID, LocalPostService::SYNC_OUTCOME_META_KEY, [ 'outcome' => SyncOutcome::SUCCESS ] );
		wp_trash_post( $post->ID );
		$this->run_scheduled_delete( $post->ID, $this->stored_result( $post->ID ), $local_post_service );

		wp_untrash_post( $post->ID );

		$this->assertSame( '', get_post_meta( $post->ID, LocalPostService::SYNC_OUTCOME_META_KEY, true ) );
	}

	public function test_trashing_a_synced_post_while_disconnected_clears_the_stored_result_without_calling_google() {
		$client             = $this->createMock( BusinessProfileApiClient::class );
		$local_post_service = $this->local_post_service_while_disconnected( $client );
		$this->register_only( new PostDeleteTrigger( $local_post_service, $this->job_repository ) );

		$post_id = $this->create_synced_post()->ID;

		$client->expects( $this->never() )->method( 'delete' );

		wp_trash_post( $post_id );
		$this->run_scheduled_delete( $post_id, $this->stored_result( $post_id ), $local_post_service );

		$this->assertSame( '', get_post_meta( $post_id, LocalPostService::RESULT_META_KEY, true ) );
	}

	public function test_permanently_deleting_a_synced_post_while_disconnected_makes_no_google_call() {
		$client             = $this->createMock( BusinessProfileApiClient::class );
		$local_post_service = $this->local_post_service_while_disconnected( $client );
		$this->register_only( new PostDeleteTrigger( $local_post_service, $this->job_repository ) );

		$post_id = $this->create_synced_post()->ID;

		$client->expects( $this->never() )->method( 'delete' );

		wp_delete_post( $post_id, true );
		$this->run_scheduled_delete( $post_id, $this->stored_result( $post_id ), $local_post_service );
	}

	public function test_restoring_a_post_trashed_while_disconnected_resets_the_sharing_toggle() {
		$client             = $this->createMock( BusinessProfileApiClient::class );
		$local_post_service = $this->local_post_service_while_disconnected( $client );
		$this->register_only( new PostDeleteTrigger( $local_post_service, $this->job_repository ) );

		$post_id = $this->create_synced_post()->ID;
		wp_trash_post( $post_id );
		$this->run_scheduled_delete( $post_id, $this->stored_result( $post_id ), $local_post_service );

		wp_untrash_post( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true ) );
	}

	public function test_restoring_a_trashed_post_whose_delete_failed_keeps_the_sharing_toggle_on() {
		$post_id = $this->create_synced_post()->ID;
		wp_trash_post( $post_id );

		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->run_scheduled_delete( $post_id, $this->stored_result( $post_id ), $this->local_post_service );

		wp_untrash_post( $post_id );

		// The stored result is still complete -- delete() threw before it could be cleared, the
		// same state a real failed delete leaves behind.
		$this->assertSame( '1', get_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true ) );
	}

	public function test_restoring_a_trashed_post_whose_delete_failed_keeps_its_last_sync_outcome() {
		$post = $this->create_synced_post();
		update_post_meta( $post->ID, LocalPostService::SYNC_OUTCOME_META_KEY, [ 'outcome' => SyncOutcome::SUCCESS ] );
		wp_trash_post( $post->ID );

		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->run_scheduled_delete( $post->ID, $this->stored_result( $post->ID ), $this->local_post_service );

		wp_untrash_post( $post->ID );

		$this->assertSame( [ 'outcome' => SyncOutcome::SUCCESS ], get_post_meta( $post->ID, LocalPostService::SYNC_OUTCOME_META_KEY, true ) );
	}

	public function test_restoring_a_duplicated_posts_copied_result_clears_its_sharing_toggle() {
		$original  = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		$duplicate = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		// Simulates a duplicate-post plugin copying the original's share meta verbatim onto a new
		// post -- the copied result's wp_post_id still points at the original, not the duplicate.
		update_post_meta( $duplicate->ID, PostShareControl::SHARE_META_KEY, true );
		update_post_meta(
			$duplicate->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'     => '111',
				'location_id'    => '222',
				'google_post_id' => '333',
				'search_url'     => 'https://g.page/r/abc',
				'wp_post_id'     => $original->ID,
			]
		);
		update_post_meta( $duplicate->ID, LocalPostService::SYNC_OUTCOME_META_KEY, [ 'outcome' => SyncOutcome::SUCCESS ] );

		wp_trash_post( $duplicate->ID );
		wp_untrash_post( $duplicate->ID );

		// get_stored_result() rejects the copied result -- its wp_post_id doesn't match the
		// duplicate's own ID -- so the restore treats the duplicate as never having been sent,
		// not as still live at Google. Nothing was ever scheduled for this post either.
		$this->assertSame( '', get_post_meta( $duplicate->ID, PostShareControl::SHARE_META_KEY, true ) );
		$this->assertSame( '', get_post_meta( $duplicate->ID, LocalPostService::SYNC_OUTCOME_META_KEY, true ) );
	}

	public function test_saving_a_post_restored_to_publish_after_a_failed_send_sends_it_once() {
		add_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10, 3 );

		$client = $this->createMock( BusinessProfileApiClient::class );
		$wp     = $this->createMock( WP::class );
		$wp->method( 'get_locale' )->willReturn( 'en_US' );
		$iso_utility = $this->createMock( ISOUtility::class );
		$iso_utility->method( 'wp_locale_to_bcp47' )->willReturn( 'en-US' );

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )->with( OptionsInterface::BUSINESS_PROFILE, [] )->willReturn(
			[
				'account_id'  => '111',
				'location_id' => '222',
			]
		);

		$local_post_service = new LocalPostService( $client, $wp, $iso_utility, $this->createMock( LocalPostImageService::class ), $this->createMock( LocationService::class ), $this->createMock( WPML::class ) );
		$local_post_service->set_options_object( $options );
		$location_service = $this->createMock( LocationService::class );
		$location_service->method( 'get_selected_location' )->willReturn( [ 'title' => 'Jane\'s Bakery' ] );
		( new PostPublishTrigger( $local_post_service, $location_service ) )->register();

		// A post whose send failed: sharing on, a failed outcome, and no stored result.
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );
		$local_post_service->set_sync_outcome( $post_id, SyncOutcome::REQUEST_FAILURE );
		wp_trash_post( $post_id );
		wp_untrash_post( $post_id );

		$client->expects( $this->once() )->method( 'post' );

		// The merchant switches sharing back on and saves, as they would on any published post.
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );
		wp_update_post(
			[
				'ID'         => $post_id,
				'post_title' => 'Edited after restore',
			]
		);
	}

	public function test_restoring_a_trashed_post_keeps_the_stored_result() {
		$post    = $this->create_synced_post();
		$post_id = $post->ID;
		wp_trash_post( $post_id );

		// The job is only scheduled here, never run -- nothing has touched the stored result yet,
		// the same state a real delete that hasn't run yet also leaves behind.
		wp_untrash_post( $post_id );

		$this->assertSame( $this->stored_result( $post_id ), get_post_meta( $post_id, LocalPostService::RESULT_META_KEY, true ) );
	}

	public function test_restoring_a_trashed_post_does_not_trigger_a_resync() {
		$post    = $this->create_synced_post();
		$post_id = $post->ID;
		wp_trash_post( $post_id );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		wp_untrash_post( $post_id );
	}

	public function test_restoring_a_trashed_post_directly_to_publish_after_a_successful_delete_does_not_trigger_a_resync() {
		// Restores straight to publish instead of draft -- the case untrashed_post is too late for.
		add_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10, 3 );

		// A genuinely successful delete, so the toggle is actually off by the time the restore
		// reaches publish -- the same precondition PostPublishTrigger relies on.
		$local_post_service = $this->local_post_service_with_a_successful_delete();
		$this->register_only( new PostDeleteTrigger( $local_post_service, $this->job_repository ) );

		$publish_service  = $this->createMock( LocalPostService::class );
		$location_service = $this->createMock( LocationService::class );
		$location_service->method( 'get_selected_location' )->willReturn( [ 'title' => 'Jane\'s Bakery' ] );
		( new PostPublishTrigger( $publish_service, $location_service ) )->register();

		$post = $this->create_synced_post();
		wp_trash_post( $post->ID );
		$this->run_scheduled_delete( $post->ID, $this->stored_result( $post->ID ), $local_post_service );

		$publish_service->expects( $this->never() )->method( 'create' );

		wp_untrash_post( $post->ID );
	}

	public function test_restoring_a_trashed_post_directly_to_publish_after_a_failed_delete_does_not_trigger_a_resync() {
		// Restores straight to publish instead of draft -- the case untrashed_post is too late for.
		add_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10, 3 );

		$publish_service = $this->createMock( LocalPostService::class );
		$publish_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			]
		);
		$location_service = $this->createMock( LocationService::class );
		$location_service->method( 'get_selected_location' )->willReturn( [ 'title' => 'Jane\'s Bakery' ] );
		( new PostPublishTrigger( $publish_service, $location_service ) )->register();

		$post = $this->create_synced_post();
		wp_trash_post( $post->ID );

		// setUp()'s own trigger wraps $this->local_post_service, whose delete() throws below -- a
		// failed delete, so the toggle is still on and the stored result still complete by the
		// time the restore reaches publish, the same precondition PostPublishTrigger's own
		// is_shared guard relies on.
		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );
		$this->run_scheduled_delete( $post->ID, $this->stored_result( $post->ID ), $this->local_post_service );

		$publish_service->expects( $this->never() )->method( 'create' );

		wp_untrash_post( $post->ID );
	}

	public function test_schedules_only_once_even_when_the_delete_hook_fires_more_than_once_for_one_post() {
		$post = $this->create_synced_post();
		$this->trigger->pre_delete( $post->ID );

		$this->delete_local_post_job->expects( $this->once() )->method( 'schedule' )->with( [ $post->ID, $this->stored_result( $post->ID ) ] );

		$this->trigger->delete_by_id( $post->ID );
		$this->trigger->delete_by_id( $post->ID );
	}

	/**
	 * Create a post of a supported type with the sharing toggle switched on.
	 *
	 * @return WP_Post
	 */
	private function create_synced_post(): WP_Post {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		update_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, $this->stored_result( $post->ID ) );

		return $post;
	}

	/**
	 * Detach every delete/restore hook setUp() already registered, then register the given
	 * trigger alone -- so a test exercising a specific service doesn't also run setUp()'s own
	 * trigger on the same hooks.
	 *
	 * @param PostDeleteTrigger $trigger
	 */
	private function register_only( PostDeleteTrigger $trigger ): void {
		remove_all_actions( 'wp_trash_post' );
		remove_all_actions( 'before_delete_post' );
		remove_all_actions( 'trashed_post' );
		remove_all_actions( 'deleted_post' );
		remove_all_actions( 'untrash_post' );

		$trigger->register();
	}

	/**
	 * Run the real job exactly as ActionScheduler would once it processes the trigger's own
	 * schedule() call -- scheduling itself only records a mocked call in this test environment,
	 * nothing runs it automatically.
	 *
	 * @param int              $post_id
	 * @param array            $result
	 * @param LocalPostService $local_post_service
	 */
	private function run_scheduled_delete( int $post_id, array $result, LocalPostService $local_post_service ): void {
		$job = new DeleteLocalPost(
			$this->createMock( ActionSchedulerInterface::class ),
			$this->createMock( ActionSchedulerJobMonitor::class ),
			$local_post_service
		);

		try {
			$job->process_items( [ $post_id, $result ] );
		} catch ( Throwable $ignored ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// A real ActionScheduler run reschedules and logs a thrown failure rather than
			// propagating it back to whatever triggered the original trash/delete -- these tests
			// only care about the state process_items() already left behind before it threw.
		}
	}

	/**
	 * A real LocalPostService whose Google delete call always succeeds, so a restore sees the
	 * stored result already cleared -- the same state a real successful delete leaves behind.
	 *
	 * @return LocalPostService
	 */
	private function local_post_service_with_a_successful_delete(): LocalPostService {
		$client = $this->createMock( BusinessProfileApiClient::class );
		$client->method( 'delete' )->willReturn( [] );
		$wp = $this->createMock( WP::class );
		$wp->method( 'get_locale' )->willReturn( 'en_US' );
		$iso_utility = $this->createMock( ISOUtility::class );
		$iso_utility->method( 'wp_locale_to_bcp47' )->willReturn( 'en-US' );

		return new LocalPostService( $client, $wp, $iso_utility, $this->createMock( LocalPostImageService::class ), $this->connected_location_service(), $this->createMock( WPML::class ) );
	}

	/**
	 * A LocationService with a location connected, so a delete reaches Google.
	 *
	 * @return LocationService
	 */
	private function connected_location_service(): LocationService {
		$location_service = $this->createMock( LocationService::class );
		$location_service->method( 'get_selected_location' )->willReturn( [ 'title' => 'Jane\'s Bakery' ] );

		return $location_service;
	}

	/**
	 * A real LocalPostService with no location connected, as after the merchant disconnects.
	 *
	 * @param BusinessProfileApiClient $client
	 *
	 * @return LocalPostService
	 */
	private function local_post_service_while_disconnected( BusinessProfileApiClient $client ): LocalPostService {
		$location_service = $this->createMock( LocationService::class );
		$location_service->method( 'get_selected_location' )->willReturn( null );

		return new LocalPostService( $client, $this->createMock( WP::class ), $this->createMock( ISOUtility::class ), $this->createMock( LocalPostImageService::class ), $location_service, $this->createMock( WPML::class ) );
	}

	/**
	 * The account/location/post-ID triple create_synced_post() stores, tied to the given
	 * WordPress post so it survives get_stored_result()'s identity check.
	 *
	 * @param int $wp_post_id
	 *
	 * @return array
	 */
	private function stored_result( int $wp_post_id ): array {
		return [
			'account_id'     => '111',
			'location_id'    => '222',
			'google_post_id' => '333',
			'search_url'     => 'https://g.page/r/abc',
			'wp_post_id'     => $wp_post_id,
		];
	}
}
