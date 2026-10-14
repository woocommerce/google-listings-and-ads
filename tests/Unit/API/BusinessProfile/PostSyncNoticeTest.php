<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostPublishTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostSyncNotice;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Request;
use WP_Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostSyncNoticeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class PostSyncNoticeTest extends UnitTest {

	protected const UNREACHABLE_MESSAGE = 'The post couldn\'t be published to Google Business Profile. Google couldn\'t be reached. Try again later.';

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var MockObject|PostPublishTrigger $post_publish_trigger */
	protected $post_publish_trigger;

	/** @var PostSyncNotice $notice */
	protected $notice;

	/** @var int $post_id */
	protected $post_id;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->local_post_service   = $this->createMock( LocalPostService::class );
		$this->post_publish_trigger = $this->createMock( PostPublishTrigger::class );

		$this->notice = new PostSyncNotice( $this->local_post_service, $this->post_publish_trigger );

		$this->post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['post'], $GLOBALS['wp_rest_additional_fields']['post'][ PostSyncNotice::FIELD_NAME ] );
		$GLOBALS['wp_rest_server'] = null;

		parent::tearDown();
	}

	public function test_register_hooks_the_rest_field_the_classic_notice_and_its_rendering() {
		$this->notice->register();

		$this->assertSame( 10, has_action( 'rest_api_init', [ $this->notice, 'register_rest_fields' ] ) );
		$this->assertSame( 20, has_action( 'wp_after_insert_post', [ $this->notice, 'keep_notice_for_next_page_load' ] ) );
		$this->assertSame( 10, has_action( 'admin_notices', [ $this->notice, 'render_notice' ] ) );
	}

	public function test_rest_field_returns_the_notice_for_a_share_attempted_in_this_request() {
		$this->mock_attempt( SyncOutcome::REQUEST_FAILURE );

		$this->assertSame(
			[
				'status'  => 'error',
				'message' => self::UNREACHABLE_MESSAGE,
			],
			$this->get_field_value()
		);
	}

	public function test_rest_field_is_null_when_no_share_was_attempted_in_this_request() {
		$this->post_publish_trigger->method( 'was_sent_in_request' )->willReturn( false );
		$this->local_post_service->expects( $this->never() )->method( 'get_sync_outcome' );

		$this->assertNull( $this->get_field_value() );
	}

	public function test_rest_field_is_null_for_a_plain_success() {
		$this->mock_attempt( SyncOutcome::SUCCESS );

		$this->assertNull( $this->get_field_value() );
	}

	public function test_rest_field_is_null_for_a_user_who_cannot_edit_the_post() {
		$this->mock_attempt( SyncOutcome::REQUEST_FAILURE );
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertNull( $this->get_field_value() );
	}

	public function test_rest_field_is_an_edit_context_read_only_field() {
		$this->notice->register_rest_fields();

		$field = $GLOBALS['wp_rest_additional_fields']['post'][ PostSyncNotice::FIELD_NAME ];

		$this->assertNull( $field['update_callback'] );
		$this->assertTrue( $field['schema']['readonly'] );
		$this->assertSame( [ 'edit' ], $field['schema']['context'] );
	}

	public function test_shows_a_classic_editor_notice_once_on_the_next_edit_screen_load() {
		$this->mock_attempt( SyncOutcome::REQUEST_FAILURE );
		$this->notice->keep_notice_for_next_page_load( $this->post_id );

		$this->open_edit_screen();

		$this->expectOutputString( '<div class="notice notice-error is-dismissible"><p>' . esc_html( self::UNREACHABLE_MESSAGE ) . '</p></div>' );
		$this->notice->render_notice();
		$this->notice->render_notice();
	}

	public function test_shows_the_unverified_location_notice_as_a_warning() {
		$this->mock_attempt( SyncOutcome::SENT_PENDING_VERIFICATION );
		$this->notice->keep_notice_for_next_page_load( $this->post_id );

		$this->open_edit_screen();

		ob_start();
		$this->notice->render_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice-warning', $output );
		$this->assertStringContainsString( 'currently unverified', $output );
	}

	public function test_keeps_no_classic_editor_notice_when_no_share_was_attempted() {
		$this->post_publish_trigger->method( 'was_sent_in_request' )->willReturn( false );
		$this->notice->keep_notice_for_next_page_load( $this->post_id );

		$this->open_edit_screen();

		$this->expectOutputString( '' );
		$this->notice->render_notice();
	}

	public function test_keeps_no_classic_editor_notice_for_a_rest_request() {
		$this->mock_attempt( SyncOutcome::REQUEST_FAILURE );
		$notice = $this->getMockBuilder( PostSyncNotice::class )
			->setConstructorArgs( [ $this->local_post_service, $this->post_publish_trigger ] )
			->onlyMethods( [ 'is_rest_request' ] )
			->getMock();
		$notice->method( 'is_rest_request' )->willReturn( true );
		$notice->keep_notice_for_next_page_load( $this->post_id );

		$this->open_edit_screen();

		$this->expectOutputString( '' );
		$this->notice->render_notice();
	}

	public function test_keeps_no_classic_editor_notice_without_a_logged_in_user() {
		$this->mock_attempt( SyncOutcome::REQUEST_FAILURE );
		wp_set_current_user( 0 );
		$this->notice->keep_notice_for_next_page_load( $this->post_id );

		$this->login_as_administrator();
		$this->open_edit_screen();

		$this->expectOutputString( '' );
		$this->notice->render_notice();
	}

	public function test_shows_no_notice_outside_the_post_edit_screen() {
		$this->mock_attempt( SyncOutcome::REQUEST_FAILURE );
		$this->notice->keep_notice_for_next_page_load( $this->post_id );

		$GLOBALS['post'] = get_post( $this->post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		set_current_screen( 'edit-post' );

		$this->expectOutputString( '' );
		$this->notice->render_notice();
	}

	/**
	 * Mock a share attempted during this request that ended with the given outcome.
	 *
	 * @param string $outcome
	 */
	private function mock_attempt( string $outcome ): void {
		$this->post_publish_trigger->method( 'was_sent_in_request' )->with( $this->post_id )->willReturn( true );
		$this->local_post_service->method( 'get_sync_outcome' )->with( $this->post_id )->willReturn(
			[
				'outcome'       => $outcome,
				'message'       => '',
				'image_omitted' => false,
			]
		);
	}

	/**
	 * Read the REST field for the test post, as the edit-context post response would.
	 *
	 * @return mixed
	 */
	private function get_field_value() {
		$this->notice->register_rest_fields();

		$callback = $GLOBALS['wp_rest_additional_fields']['post'][ PostSyncNotice::FIELD_NAME ]['get_callback'];

		return $callback( [ 'id' => $this->post_id ], PostSyncNotice::FIELD_NAME, new WP_REST_Request(), 'post' );
	}

	/**
	 * Set the current screen to the Classic Editor for the test post.
	 */
	private function open_edit_screen(): void {
		$GLOBALS['post'] = get_post( $this->post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$screen = WP_Screen::get( 'post' );
		$screen->is_block_editor( false );
		set_current_screen( $screen );
	}
}
