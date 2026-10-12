<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostPublishTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostPublishTriggerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile
 */
class PostPublishTriggerTest extends UnitTest {

	/** @var MockObject|LocalPostService $local_post_service */
	protected $local_post_service;

	/** @var PostPublishTrigger $trigger */
	protected $trigger;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->trigger            = new PostPublishTrigger( $this->local_post_service );
	}

	public function test_register_hooks_transition_post_status_to_handle_transition_post_status() {
		$this->trigger->register();

		$this->assertSame(
			10,
			has_action( 'transition_post_status', [ $this->trigger, 'handle_transition_post_status' ] )
		);
	}

	public function test_sends_a_shared_post_on_a_genuine_publish_transition() {
		$post = $this->create_shared_post();

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->handle_transition_post_status( 'publish', 'draft', $post );
	}

	/**
	 * @dataProvider data_provider_non_publish_transitions
	 *
	 * @param string $new_status
	 * @param string $old_status
	 */
	public function test_does_not_send_on_a_transition_that_is_not_a_genuine_publish( string $new_status, string $old_status ) {
		$post = $this->create_shared_post();

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->handle_transition_post_status( $new_status, $old_status, $post );
	}

	/**
	 * @return array
	 */
	public function data_provider_non_publish_transitions(): array {
		return [
			'into draft'                 => [ 'draft', 'auto-draft' ],
			'into pending'               => [ 'pending', 'draft' ],
			'into future (scheduled)'    => [ 'future', 'draft' ],
			'into private'               => [ 'private', 'draft' ],
			'already published (a save)' => [ 'publish', 'publish' ],
		];
	}

	public function test_sends_a_scheduled_posts_automatic_publish_the_same_way_as_a_manual_publish() {
		$post = $this->create_shared_post();

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		// WordPress's own scheduler ( wp_publish_post(), run by WP-Cron ) calls
		// wp_transition_post_status( 'publish', $old_status, $post ) exactly like a manual publish --
		// there is no separate hook or code path to trigger here, so exercising the same callback
		// with the scheduled post's own prior status ('future') is the real behaviour under test.
		$this->trigger->handle_transition_post_status( 'publish', 'future', $post );
	}

	public function test_sends_only_once_even_when_the_transition_fires_more_than_once_for_one_publish_action() {
		$post = $this->create_shared_post();

		$this->local_post_service->expects( $this->once() )->method( 'create' )->with( $post );

		$this->trigger->handle_transition_post_status( 'publish', 'draft', $post );
		$this->trigger->handle_transition_post_status( 'publish', 'draft', $post );
	}

	public function test_does_not_send_a_post_whose_sharing_toggle_is_off() {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, false );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->handle_transition_post_status( 'publish', 'draft', $post );
	}

	public function test_does_not_send_a_post_of_an_unsupported_post_type() {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		$this->trigger->handle_transition_post_status( 'publish', 'draft', $post );
	}

	public function test_does_not_propagate_an_exception_from_the_create_call() {
		$post = $this->create_shared_post();

		$this->local_post_service->method( 'create' )->willThrowException( new Exception( 'Network error.' ) );

		$this->trigger->handle_transition_post_status( 'publish', 'draft', $post );

		$this->assertTrue( true );
	}

	/**
	 * Create a post of a supported type with the sharing toggle switched on.
	 *
	 * @return WP_Post
	 */
	private function create_shared_post(): WP_Post {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );

		return $post;
	}
}
