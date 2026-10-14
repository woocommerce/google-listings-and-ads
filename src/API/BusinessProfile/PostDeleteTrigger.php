<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Exception;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostDeleteTrigger
 *
 * Removes a post's Google Business Profile local post when the WordPress post is trashed or
 * permanently deleted, and resets its sharing state if later restored.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostDeleteTrigger implements Service, Registerable {

	/** @var LocalPostService */
	private $local_post_service;

	/** @var array<int,array> Stored results captured before their trash/delete hook fires, keyed by post ID. */
	private $pending_delete = [];

	/**
	 * PostDeleteTrigger constructor.
	 *
	 * @param LocalPostService $local_post_service
	 */
	public function __construct( LocalPostService $local_post_service ) {
		$this->local_post_service = $local_post_service;
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
	 * Delete the captured post's Google Business Profile local post.
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

		$result = get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true );
		$result = is_array( $result ) ? $result : [];

		if ( ! LocalPostService::has_complete_result( $result ) ) {
			return;
		}

		$this->pending_delete[ $post->ID ] = $result;
	}

	/**
	 * Delete the Google Business Profile local post for a previously captured post, at most once
	 * per request.
	 *
	 * @param int $post_id
	 */
	protected function handle_delete( int $post_id ): void {
		if ( ! isset( $this->pending_delete[ $post_id ] ) ) {
			return;
		}
		$result = $this->pending_delete[ $post_id ];
		unset( $this->pending_delete[ $post_id ] );

		try {
			// TODO: move this to a background job -- a bulk trash or delete of several shared
			// posts makes one synchronous Google call per post in the same admin request.
			$this->local_post_service->delete( $post_id, $result );
		} catch ( Exception $exception ) {
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		}
	}

	/**
	 * Clear a restored post's sharing toggle, so it starts fresh rather than resyncing stale
	 * state. The stored result is left alone: if the earlier delete failed, it's the only
	 * pointer left to a post that's still live at Google, and clearing it here would let a later
	 * reshare create a duplicate.
	 *
	 * @param int $post_id
	 */
	protected function handle_restore( int $post_id ): void {
		if ( ! in_array( get_post_type( $post_id ), LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		delete_post_meta( $post_id, PostShareControl::SHARE_META_KEY );
	}
}
