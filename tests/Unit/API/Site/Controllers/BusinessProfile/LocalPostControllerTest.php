<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile\LocalPostController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPostControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile
 */
class LocalPostControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var MockObject|LocationService $location_service */
	protected $location_service;

	/** @var LocalPostController $controller */
	protected $controller;

	/** @var WP_Post $post */
	protected $post;

	/** @var array|null The connected location, or null when none is. */
	protected $selected_location = [ 'id' => '222' ];

	protected const ROUTE_CHECK = '/wc/gla/business-profile/posts/(?P<post_id>[\d]+)/check';

	public function setUp(): void {
		parent::setUp();

		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->location_service   = $this->createMock( LocationService::class );
		$this->controller         = new LocalPostController( $this->server, $this->local_post_service, $this->location_service );
		$this->controller->register();

		$this->post = $this->factory()->post->create_and_get();
		update_post_meta( $this->post->ID, PostShareControl::SHARE_META_KEY, true );

		$this->location_service->method( 'get_selected_location' )->willReturnCallback(
			function () {
				return $this->selected_location;
			}
		);
		$this->local_post_service->method( 'get_share_status' )->willReturn(
			[
				'is_shared'  => true,
				'search_url' => 'https://g.page/r/abc',
			]
		);
	}

	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_CHECK, $this->server->get_routes() );
	}

	public function test_a_post_that_still_exists_is_left_as_it_is() {
		$this->given_a_stored_result();
		$this->local_post_service->expects( $this->once() )
			->method( 'exists_on_google' )
			->with( $this->get_stored_result() )
			->willReturn( true );
		$this->local_post_service->expects( $this->never() )->method( 'unlink_deleted_post' );

		$response = $this->check_post();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			[
				'checked'          => true,
				'check_failure'    => null,
				'is_share_enabled' => true,
				'share_status'     => [
					'is_shared'  => true,
					'search_url' => 'https://g.page/r/abc',
				],
				'sync_outcome'     => null,
			],
			$response->get_data()
		);
	}

	public function test_a_post_deleted_on_google_is_unlinked() {
		$this->given_a_stored_result();
		$this->local_post_service->method( 'exists_on_google' )->willReturn( false );
		$this->local_post_service->expects( $this->once() )
			->method( 'unlink_deleted_post' )
			->with( $this->post->ID, '333' );

		$response = $this->check_post();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['checked'] );
		$this->assertNull( $response->get_data()['check_failure'] );
	}

	public function test_returns_the_state_read_after_the_unlink() {
		$this->given_a_stored_result();
		$not_found = [
			'outcome'         => SyncOutcome::NOT_FOUND,
			'message'         => '',
			'image_omitted'   => false,
			'display_message' => 'The post no longer exists on Google Business Profile.',
			'display_status'  => 'error',
		];
		$this->local_post_service->method( 'exists_on_google' )->willReturn( false );
		$this->local_post_service->method( 'unlink_deleted_post' )->willReturnCallback(
			function ( int $post_id ) {
				update_post_meta( $post_id, PostShareControl::SHARE_META_KEY, false );
				return true;
			}
		);
		$this->local_post_service->method( 'get_sync_outcome_for_display' )->willReturn( $not_found );

		$data = $this->check_post()->get_data();

		$this->assertFalse( $data['is_share_enabled'] );
		$this->assertSame( $not_found, $data['sync_outcome'] );
	}

	public function test_makes_no_google_call_when_no_location_is_connected() {
		$this->selected_location = null;
		$this->given_a_stored_result();

		$this->local_post_service->expects( $this->never() )->method( 'exists_on_google' );

		$data = $this->check_post()->get_data();

		$this->assertFalse( $data['checked'] );
		$this->assertNull( $data['check_failure'] );
	}

	public function test_makes_no_google_call_for_a_post_that_was_never_shared() {
		$this->local_post_service->method( 'get_stored_result' )->willReturn( [] );
		$this->local_post_service->expects( $this->never() )->method( 'exists_on_google' );

		$data = $this->check_post()->get_data();

		$this->assertFalse( $data['checked'] );
		$this->assertNull( $data['check_failure'] );
	}

	/**
	 * @dataProvider data_provider_waiting_outcomes
	 *
	 * @param string $outcome
	 */
	public function test_makes_no_google_call_while_a_send_or_removal_is_waiting_to_run( string $outcome ) {
		$this->given_a_stored_result();
		$this->local_post_service->method( 'get_sync_outcome' )->willReturn(
			[
				'outcome'       => $outcome,
				'message'       => '',
				'image_omitted' => false,
			]
		);
		$this->local_post_service->expects( $this->never() )->method( 'exists_on_google' );

		$this->assertFalse( $this->check_post()->get_data()['checked'] );
	}

	/**
	 * @return array
	 */
	public function data_provider_waiting_outcomes(): array {
		return [
			'send waiting'    => [ SyncOutcome::QUEUED ],
			'removal waiting' => [ SyncOutcome::UNSHARE_QUEUED ],
		];
	}

	public function test_a_failed_check_reports_the_failure_and_unlinks_nothing() {
		$this->given_a_stored_result();
		$this->local_post_service->method( 'exists_on_google' )->willThrowException(
			new BusinessProfileApiException( 503, [], __METHOD__ )
		);
		$this->local_post_service->expects( $this->never() )->method( 'unlink_deleted_post' );

		$before = did_action( 'woocommerce_gla_exception' );

		$response = $this->check_post();

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['checked'] );
		$this->assertSame( LocalPostController::CHECK_FAILURE_ERROR, $response->get_data()['check_failure'] );
		$this->assertTrue( $response->get_data()['is_share_enabled'] );
		$this->assertSame( $before + 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_a_check_that_needs_a_reconnect_says_so() {
		$this->given_a_stored_result();
		$this->local_post_service->method( 'exists_on_google' )->willThrowException( AccountReconnect::google_disconnected() );

		$data = $this->check_post()->get_data();

		$this->assertFalse( $data['checked'] );
		$this->assertSame( LocalPostController::CHECK_FAILURE_RECONNECT, $data['check_failure'] );
	}

	public function test_returns_not_found_for_a_post_type_that_cannot_be_shared() {
		$page = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );

		$this->local_post_service->expects( $this->never() )->method( 'exists_on_google' );

		$response = $this->do_request( "/wc/gla/business-profile/posts/{$page->ID}/check", 'POST' );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_refuses_a_missing_post() {
		$this->local_post_service->expects( $this->never() )->method( 'exists_on_google' );

		$response = $this->do_request( '/wc/gla/business-profile/posts/999999/check', 'POST' );

		// No one can edit a post that doesn't exist, so the permission check refuses it first.
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_a_user_who_cannot_edit_the_post_is_refused() {
		$this->given_a_stored_result();
		$this->local_post_service->expects( $this->never() )->method( 'exists_on_google' );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = $this->check_post();

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_an_editor_who_can_edit_the_post_is_allowed() {
		$this->given_a_stored_result();
		$this->local_post_service->method( 'exists_on_google' )->willReturn( true );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertSame( 200, $this->check_post()->get_status() );
	}

	/**
	 * Send the check for the test post.
	 *
	 * @return object The response.
	 */
	private function check_post(): object {
		return $this->do_request( "/wc/gla/business-profile/posts/{$this->post->ID}/check", 'POST' );
	}

	private function given_a_stored_result(): void {
		$this->local_post_service->method( 'get_stored_result' )->willReturn( $this->get_stored_result() );
	}

	/**
	 * A stored result, shaped as `LocalPostService::create()` stores it.
	 *
	 * @return array
	 */
	private function get_stored_result(): array {
		return [
			'account_id'     => '111',
			'location_id'    => '222',
			'google_post_id' => '333',
			'search_url'     => 'https://g.page/r/abc',
			'wp_post_id'     => $this->post->ID,
		];
	}
}
