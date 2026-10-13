<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostDeleteTrigger;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
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

	/** @var PostDeleteTrigger $trigger */
	protected $trigger;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->local_post_service = $this->createMock( LocalPostService::class );
		$this->trigger            = new PostDeleteTrigger( $this->local_post_service );
		$this->trigger->register();
	}

	public function test_trashing_a_synced_post_triggers_delete() {
		$post = $this->create_synced_post();

		$this->local_post_service->expects( $this->once() )->method( 'delete' )->with( $post );

		wp_trash_post( $post->ID );
	}

	public function test_permanently_deleting_a_synced_post_triggers_delete_without_a_prior_trash() {
		$post = $this->create_synced_post();

		$this->local_post_service->expects( $this->once() )->method( 'delete' )->with( $post );

		wp_delete_post( $post->ID, true );
	}

	public function test_does_not_delete_an_unsynced_post() {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );

		$this->local_post_service->expects( $this->never() )->method( 'delete' );

		wp_trash_post( $post->ID );
	}

	public function test_does_not_delete_a_post_of_an_unsupported_post_type() {
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		update_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true );
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			]
		);

		$this->local_post_service->expects( $this->never() )->method( 'delete' );

		wp_trash_post( $post->ID );
	}

	public function test_restoring_a_trashed_post_resets_the_sharing_toggle_and_the_stored_result() {
		$post    = $this->create_synced_post();
		$post_id = $post->ID;
		wp_trash_post( $post_id );

		wp_untrash_post( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true ) );
		$this->assertSame( '', get_post_meta( $post_id, LocalPostService::RESULT_META_KEY, true ) );
	}

	public function test_restoring_a_trashed_post_does_not_trigger_a_resync() {
		$post    = $this->create_synced_post();
		$post_id = $post->ID;
		wp_trash_post( $post_id );

		$this->local_post_service->expects( $this->never() )->method( 'create' );

		wp_untrash_post( $post_id );
	}

	public function test_does_not_propagate_an_exception_from_the_delete_call() {
		$post = $this->create_synced_post();

		$this->local_post_service->method( 'delete' )->willThrowException( new Exception( 'Network error.' ) );

		wp_trash_post( $post->ID );

		$this->assertTrue( true );
	}

	public function test_deletes_only_once_even_when_the_delete_hook_fires_more_than_once_for_one_post() {
		$post = $this->create_synced_post();
		$this->trigger->pre_delete( $post->ID );

		$this->local_post_service->expects( $this->once() )->method( 'delete' )->with( $post );

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
		update_post_meta(
			$post->ID,
			LocalPostService::RESULT_META_KEY,
			[
				'account_id'  => '111',
				'location_id' => '222',
				'post_id'     => '333',
				'search_url'  => 'https://g.page/r/abc',
			]
		);

		return $post;
	}
}
