<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\MissingLocalPostIdException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostPublishTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\WPHookTrait;
use Error;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Post;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostPublishTriggerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class PostPublishTriggerTest extends UnitTest {

	use WPHookTrait;

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var PostPublishTrigger $trigger */
	protected $trigger;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->local_post_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => false,
				'search_url' => '',
			]
		);

		$this->location_service = $this->createMock( LocationService::class );
		$this->location_service->method( 'get_selected_location' )->willReturn( [ 'title' => 'Jane\'s Bakery' ] );

		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->location_service );
	}

	public function test_register_hooks_wp_after_insert_post_to_publish_to_business_profile() {
		$this->trigger->register();

		$this->assertSame(
			10,
			has_action( 'wp_after_insert_post', [ $this->trigger, 'publish_to_business_profile' ] )
		);
	}

	public function test_sends_a_shared_post_on_a_genuine_publish() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	/**
	 * @dataProvider data_provider_non_publish_transitions
	 *
	 * @param string $new_status
	 * @param string $old_status
	 */
	public function test_does_not_send_on_a_transition_that_is_not_a_genuine_publish( string $new_status, string $old_status ) {
		$post = $this->create_post_with_status( $new_status, [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		$post_before = $this->create_post_with_status( $old_status );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	/**
	 * @return array
	 */
	public function data_provider_non_publish_transitions(): array {
		return [
			'into draft'              => [ 'draft', 'auto-draft' ],
			'into pending'            => [ 'pending', 'draft' ],
			'into future (scheduled)' => [ 'future', 'draft' ],
			'into private'            => [ 'private', 'draft' ],
		];
	}

	public function test_sends_a_scheduled_posts_automatic_publish_the_same_way_as_a_manual_publish() {
		$post        = $this->create_shared_post();
		$post_before = $this->create_post_with_status( 'future' );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		// wp_publish_post(), run by WP-Cron for a scheduled post, fires wp_after_insert_post the
		// same way wp_insert_post() does for a manual publish -- no separate code path to trigger.
		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_sends_only_once_even_when_the_hook_fires_more_than_once_for_one_publish_action() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_was_sent_in_request_is_true_only_for_a_post_sent_during_the_request() {
		$post        = $this->create_shared_post();
		$other       = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->assertFalse( $this->trigger->was_sent_in_request( $post->ID ) );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertTrue( $this->trigger->was_sent_in_request( $post->ID ) );
		$this->assertFalse( $this->trigger->was_sent_in_request( $other->ID ) );
	}

	public function test_was_sent_in_request_is_true_after_a_failed_send() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );
		$this->local_post_service->method( 'create' )->willThrowException( new Exception( 'Google is unreachable.' ) );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertTrue( $this->trigger->was_sent_in_request( $post->ID ) );
	}

	public function test_does_not_send_a_post_whose_sharing_toggle_is_off() {
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, false );
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_send_a_post_of_an_unsupported_post_type() {
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_send_when_no_location_is_connected() {
		$this->location_service = $this->createMock( LocationService::class );
		$this->location_service->method( 'get_selected_location' )->willReturn( null );
		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->location_service );

		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_send_a_post_that_is_already_shared() {
		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->local_post_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			]
		);
		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->location_service );

		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_propagate_an_exception_from_the_create_call_and_reports_it() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->method( 'create' )->willThrowException( new Exception( 'Network error.' ) );

		$before = did_action( 'woocommerce_gla_exception' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertSame( $before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	/**
	 * @dataProvider data_provider_failure_outcomes
	 *
	 * @param string $outcome
	 */
	public function test_retries_the_send_when_a_published_post_is_saved_after_a_failure( string $outcome ) {
		$this->local_post_service->method( 'get_sync_outcome' )->willReturn(
			[
				'outcome' => $outcome,
				'message' => '',
			]
		);
		$post        = $this->create_shared_post();
		$post_before = $this->create_post_with_status( 'publish' );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	/**
	 * @return array
	 */
	public function data_provider_failure_outcomes(): array {
		return [
			'request failure'      => [ SyncOutcome::REQUEST_FAILURE ],
			'content rejection'    => [ SyncOutcome::CONTENT_REJECTION ],
			'account restriction'  => [ SyncOutcome::ACCOUNT_RESTRICTION ],
			'account disconnected' => [ SyncOutcome::ACCOUNT_DISCONNECTED ],
		];
	}

	/**
	 * @dataProvider data_provider_outcomes_that_are_not_retried
	 *
	 * @param string $outcome
	 */
	public function test_does_not_resend_when_a_published_post_is_saved_after_an_outcome_that_is_not_a_failure( string $outcome ) {
		$this->local_post_service->method( 'get_sync_outcome' )->willReturn(
			[
				'outcome' => $outcome,
				'message' => '',
			]
		);
		$post        = $this->create_shared_post();
		$post_before = $this->create_post_with_status( 'publish' );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	/**
	 * @return array
	 */
	public function data_provider_outcomes_that_are_not_retried(): array {
		return [
			'success'                   => [ SyncOutcome::SUCCESS ],
			'sent pending verification' => [ SyncOutcome::SENT_PENDING_VERIFICATION ],
			'not found'                 => [ SyncOutcome::NOT_FOUND ],
		];
	}

	public function test_sends_a_published_post_saved_without_any_earlier_send() {
		$this->local_post_service->method( 'get_sync_outcome' )->willReturn( null );
		$post        = $this->create_shared_post();
		$post_before = $this->create_post_with_status( 'publish' );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_does_not_retry_during_the_block_editors_follow_up_meta_box_save() {
		$this->local_post_service->method( 'get_sync_outcome' )->willReturn(
			[
				'outcome' => SyncOutcome::REQUEST_FAILURE,
				'message' => '',
			]
		);
		$post        = $this->create_shared_post();
		$post_before = $this->create_post_with_status( 'publish' );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$_GET['meta-box-loader'] = '1';
		try {
			$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
		} finally {
			unset( $_GET['meta-box-loader'] );
		}
	}

	public function test_does_not_retry_a_failed_send_once_sharing_is_switched_off() {
		$this->local_post_service->method( 'get_sync_outcome' )->willReturn(
			[
				'outcome' => SyncOutcome::REQUEST_FAILURE,
				'message' => '',
			]
		);
		$post = $this->create_shared_post();
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, false );
		$post_before = $this->create_post_with_status( 'publish' );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_a_rest_update_retries_a_failed_send_once() {
		$this->trigger->register();
		$this->register_share_meta();
		$this->login_as_administrator();
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );
		$this->local_post_service->method( 'get_sync_outcome' )->willReturn(
			[
				'outcome' => SyncOutcome::CONTENT_REJECTION,
				'message' => '',
			]
		);

		$this->local_post_service->expects( $this->once() )->method( 'create' );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'title' => 'Edited title' ] );
		rest_do_request( $request );
	}

	public function test_leaves_the_success_outcome_to_the_service_once_google_accepts_the_post() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );
		$this->local_post_service->expects( $this->never() )->method( 'set_sync_outcome' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_stores_the_classified_outcome_and_googles_message_when_the_send_fails() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

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

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_stores_success_when_google_accepts_the_post_without_returning_its_id() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502 )
		);
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::SUCCESS, '', false );

		$before = did_action( 'woocommerce_gla_exception' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertSame( $before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_stores_pending_verification_when_google_accepts_a_post_for_an_unverified_location_without_returning_its_id() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502, false, SyncOutcome::SENT_PENDING_VERIFICATION )
		);
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::SENT_PENDING_VERIFICATION, '', false );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_keeps_the_omitted_image_flag_when_google_accepts_the_post_without_returning_its_id() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502, true )
		);
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::SUCCESS, '', true );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_reports_a_create_sync_success_once_google_accepts_the_post() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->expect_do_action_once( 'woocommerce_gla_business_profile_sync_success', [ [ 'type' => 'create' ] ] );
		$this->expect_do_action_never( 'woocommerce_gla_business_profile_sync_failure' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_reports_a_create_sync_success_when_google_accepts_the_post_without_returning_its_id() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502 )
		);

		$this->expect_do_action_once( 'woocommerce_gla_business_profile_sync_success', [ [ 'type' => 'create' ] ] );
		$this->expect_do_action_never( 'woocommerce_gla_business_profile_sync_failure' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_reports_a_create_sync_failure_with_googles_error_status() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

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

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	/**
	 * @dataProvider data_provider_failures_without_a_google_error
	 *
	 * @param callable $create_failure
	 */
	public function test_reports_a_sync_failure_with_an_empty_reason_when_google_gave_no_error( callable $create_failure ) {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

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

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
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

	public function test_a_throwing_sync_success_listener_does_not_break_the_save_or_store_a_failure() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

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

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertSame( [ 'A woocommerce_gla_business_profile_sync_success callback threw: Listener failed.' ], $logged );
	}

	public function test_a_throwing_sync_failure_listener_does_not_break_the_save_or_skip_the_failure_report() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

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

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertSame( [ 'A woocommerce_gla_business_profile_sync_failure callback threw: Listener failed.' ], $logged );
		$this->assertSame( $exceptions_before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_does_not_report_a_sync_when_nothing_is_sent() {
		$post        = $this->create_post_with_status( 'publish', [ 'post_type' => 'post' ] );
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->expect_do_action_never( 'woocommerce_gla_business_profile_sync_success' );
		$this->expect_do_action_never( 'woocommerce_gla_business_profile_sync_failure' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );
	}

	public function test_a_save_after_google_returned_no_post_id_does_not_send_a_duplicate() {
		$this->trigger->register();
		$this->register_share_meta();
		$this->login_as_administrator();
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );

		$stored = null;
		$this->local_post_service->method( 'set_sync_outcome' )->willReturnCallback(
			function ( int $id, string $outcome, string $message = '' ) use ( &$stored ) {
				$stored = [
					'outcome' => $outcome,
					'message' => $message,
				];
			}
		);
		$this->local_post_service->method( 'get_sync_outcome' )->willReturnCallback(
			function () use ( &$stored ) {
				return $stored;
			}
		);
		$this->local_post_service->expects( $this->once() )->method( 'create' )->willThrowException(
			new MissingLocalPostIdException( 'Google Business Profile returned no post identifier.', 502 )
		);

		$publish = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$publish->set_body_params( [ 'status' => 'publish' ] );
		rest_do_request( $publish );

		// A later save happens in its own request, so the per-request guard doesn't apply.
		$this->trigger = new PostPublishTrigger( $this->local_post_service, $this->location_service );
		remove_all_actions( 'wp_after_insert_post' );
		$this->trigger->register();

		$update = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$update->set_body_params( [ 'title' => 'Edited title' ] );
		rest_do_request( $update );
	}

	public function test_does_not_propagate_an_error_from_the_create_call_and_reports_it_by_message() {
		$post        = $this->create_shared_post();
		$post_before = $this->factory()->post->create_and_get( [ 'post_status' => 'draft' ] );

		$this->local_post_service->method( 'create' )->willThrowException( new Error( 'Call to a member function on null.' ) );
		$this->local_post_service->expects( $this->once() )
			->method( 'set_sync_outcome' )
			->with( $post->ID, SyncOutcome::REQUEST_FAILURE, '' );

		$exceptions_before = did_action( 'woocommerce_gla_exception' );
		$errors_before     = did_action( 'woocommerce_gla_error' );

		$this->trigger->publish_to_business_profile( $post->ID, $post, true, $post_before );

		$this->assertSame( $exceptions_before, did_action( 'woocommerce_gla_exception' ) );
		$this->assertSame( $errors_before + 1, did_action( 'woocommerce_gla_error' ) );
	}

	public function test_a_failed_send_leaves_the_post_published() {
		$this->trigger->register();
		$this->register_share_meta();
		$this->login_as_administrator();
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );
		update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true );

		$this->local_post_service->method( 'create' )->willThrowException( new BusinessProfileApiException( 503, [], __METHOD__ ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'status' => 'publish' ] );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'publish', get_post_status( $post_id ) );
	}

	public function test_sends_a_post_whose_sharing_toggle_is_saved_in_the_same_rest_request_as_the_publish() {
		$this->trigger->register();
		$this->register_share_meta();
		$this->login_as_administrator();
		$post_id = $this->factory()->post->create( [ 'post_status' => 'draft' ] );

		$this->local_post_service->expects( $this->once() )->method( 'create' );

		// The block editor's own save shape -- status and meta in one REST request -- is what
		// actually exercises the fix; wp_update_post()'s meta_input writes too early to catch it.
		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params(
			[
				'status' => 'publish',
				'meta'   => [ PostShareControl::SHARE_META_KEY => true ],
			]
		);
		rest_do_request( $request );
	}

	/**
	 * Register the share meta's REST schema, as PostShareControl does on `init`, so a REST
	 * request can write it.
	 */
	private function register_share_meta(): void {
		( new PostShareControl(
			$this->createMock( AssetsHandlerInterface::class ),
			$this->local_post_service,
			$this->location_service,
			$this->createMock( OptionsInterface::class )
		) )->register_post_meta();
	}

	/**
	 * Create a post of a supported type with the sharing toggle switched on.
	 *
	 * @return WP_Post
	 */
	private function create_shared_post(): WP_Post {
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );

		return $post;
	}

	/**
	 * Create a post with a given status. A `future` status needs a future `post_date`, or
	 * wp_insert_post() silently publishes it immediately instead.
	 *
	 * @param string $status
	 * @param array  $args Additional factory args.
	 *
	 * @return WP_Post
	 */
	private function create_post_with_status( string $status, array $args = [] ): WP_Post {
		if ( 'future' === $status ) {
			$args['post_date'] = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		}

		return $this->factory()->post->create_and_get( array_merge( [ 'post_status' => $status ], $args ) );
	}
}
