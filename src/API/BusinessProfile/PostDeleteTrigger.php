<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteLocalPost;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostDeleteTrigger
 *
 * Schedules the removal of a post's Google Business Profile local post when the WordPress post
 * is trashed or permanently deleted, and resets its sharing state if later restored.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostDeleteTrigger implements Service, Registerable {

	/** @var LocalPostService */
	private $local_post_service;

	/** @var JobRepository */
	private $job_repository;

	/** @var array<int,array> Stored results captured before their trash/delete hook fires, keyed by post ID. */
	private $pending_delete = [];

	/**
	 * PostDeleteTrigger constructor.
	 *
	 * @param LocalPostService $local_post_service
	 * @param JobRepository    $job_repository
	 */
	public function __construct( LocalPostService $local_post_service, JobRepository $job_repository ) {
		$this->local_post_service = $local_post_service;
		$this->job_repository     = $job_repository;
	}

	/**
	 * Register the trash, delete, and restore hooks.
	 */
	public function register(): void {
		// Capture while meta is still readable.
		add_action( 'wp_trash_post', [ $this, 'pre_delete' ] );
		add_action( 'before_delete_post', [ $this, 'pre_delete' ] );

		// A trash and a permanent delete both remove the Google post.
		add_action( 'trashed_post', [ $this, 'delete_by_id' ] );
		add_action( 'deleted_post', [ $this, 'delete_by_id' ] );

		// Fires before wp_update_post(), so a direct trash-to-publish restore still sees the toggle off.
		add_action( 'untrash_post', [ $this, 'restore_by_id' ] );
	}

	/**
	 * Capture the post before it's trashed or deleted.
	 *
	 * @param int $post_id
	 */
	public function pre_delete( $post_id ): void {
		$post = get_post( (int) $post_id );

		if ( $post instanceof WP_Post ) {
			$this->handle_pre_delete( $post );
		}
	}

	/**
	 * Schedule the deletion of the captured post's Google Business Profile local post.
	 *
	 * @param int $post_id
	 */
	public function delete_by_id( $post_id ): void {
		$this->handle_delete( (int) $post_id );
	}

	/**
	 * Reset a restored post's sharing state.
	 *
	 * @param int $post_id
	 */
	public function restore_by_id( $post_id ): void {
		$this->handle_restore( (int) $post_id );
	}

	/**
	 * Capture a supported, synced post's stored result for a later delete call. Captured here,
	 * not read again at delete_by_id(), because a permanent delete clears this post's own meta
	 * rows before deleted_post fires -- there would be nothing left to read there.
	 *
	 * @param WP_Post $post
	 */
	protected function handle_pre_delete( WP_Post $post ): void {
		if ( ! in_array( $post->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		$result = $this->local_post_service->get_stored_result( $post->ID );

		if ( ! LocalPostService::has_complete_result( $result ) ) {
			return;
		}

		$this->pending_delete[ $post->ID ] = $result;
	}

	/**
	 * Schedule the Google Business Profile local post delete for a previously captured post, at
	 * most once per request.
	 *
	 * @param int $post_id
	 */
	protected function handle_delete( int $post_id ): void {
		if ( ! isset( $this->pending_delete[ $post_id ] ) ) {
			return;
		}
		$result = $this->pending_delete[ $post_id ];
		unset( $this->pending_delete[ $post_id ] );

		$this->job_repository->get( DeleteLocalPost::class )->schedule( [ $post_id, $result ] );
	}

	/**
	 * Clear a restored post's last sync outcome always, and its sharing toggle too unless the
	 * stored result is still complete.
	 *
	 * A still-complete result now means either a delete that genuinely failed, or one that's
	 * simply still queued and hasn't run yet -- not cancelled on restore, matching the equivalent,
	 * already-shipped coupon behavior. Either way `get_share_status()` (reading that same stored
	 * result) already keeps a resend blocked for as long as the post genuinely still looks live,
	 * so leaving the toggle and result alone here is safe. But the last sync outcome is cleared
	 * regardless: leaving a stale "success" in place would separately block `sends_on_save()`
	 * forever, even once the result does eventually clear -- a queued delete finally succeeding,
	 * say -- stranding the post sharing-on with no further save ever able to retry it.
	 *
	 * @param int $post_id
	 */
	protected function handle_restore( int $post_id ): void {
		if ( ! in_array( get_post_type( $post_id ), LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		$result = $this->local_post_service->get_stored_result( $post_id );

		delete_post_meta( $post_id, LocalPostService::SYNC_OUTCOME_META_KEY );

		if ( LocalPostService::has_complete_result( $result ) ) {
			return;
		}

		delete_post_meta( $post_id, PostShareControl::SHARE_META_KEY );
	}
}
