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

	/** @var array<int,bool> Post IDs captured as synced before their trash/delete hook fires. */
	private $pending_delete = [];

	/** @var array<int,bool> Post IDs already deleted from Google during the current request. */
	private $deleted_in_request = [];

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
		// wp_trash_post/before_delete_post fire while the post and its meta are still fully
		// readable -- capture here, since by trashed_post/deleted_post time a permanently
		// deleted post's own meta rows may already be gone.
		add_action( 'wp_trash_post', [ $this, 'pre_delete' ] );
		add_action( 'before_delete_post', [ $this, 'pre_delete' ] );

		// A trash and a permanent delete both remove the Google post.
		add_action( 'trashed_post', [ $this, 'delete_by_id' ] );
		add_action( 'deleted_post', [ $this, 'delete_by_id' ] );

		// A restored post starts fresh: no automatic resync, sharing defaults back off.
		add_action( 'untrashed_post', [ $this, 'restore_by_id' ] );
	}

	/**
	 * Capture the post before it's trashed or deleted.
	 *
	 * @param int $post_id
	 */
	public function pre_delete( int $post_id ): void {
		$post = get_post( $post_id );

		if ( $post instanceof WP_Post ) {
			$this->handle_pre_delete( $post );
		}
	}

	/**
	 * Delete the captured post's Google Business Profile local post.
	 *
	 * @param int $post_id
	 */
	public function delete_by_id( int $post_id ): void {
		$this->handle_delete( $post_id );
	}

	/**
	 * Reset a restored post's sharing state.
	 *
	 * @param int $post_id
	 */
	public function restore_by_id( int $post_id ): void {
		$this->handle_restore( $post_id );
	}

	/**
	 * Capture a supported post's current state for a later delete call.
	 *
	 * @param WP_Post $post
	 */
	protected function handle_pre_delete( WP_Post $post ): void {
		if ( ! in_array( $post->post_type, PostShareControl::POST_TYPES, true ) ) {
			return;
		}

		$result = get_post_meta( $post->ID, LocalPostService::RESULT_META_KEY, true );

		if ( empty( $result['post_id'] ) ) {
			return;
		}

		$this->pending_delete[ $post->ID ] = true;
	}

	/**
	 * Delete the Google Business Profile local post for a previously captured post, at most once
	 * per request.
	 *
	 * @param int $post_id
	 */
	protected function handle_delete( int $post_id ): void {
		if ( ! isset( $this->pending_delete[ $post_id ] ) || isset( $this->deleted_in_request[ $post_id ] ) ) {
			return;
		}
		$this->deleted_in_request[ $post_id ] = true;

		try {
			$this->local_post_service->delete( $post_id );
		} catch ( Exception $exception ) {
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		}
	}

	/**
	 * Clear a restored post's sharing toggle and stored Google Business Profile result, so it
	 * starts fresh rather than resyncing stale state.
	 *
	 * @param int $post_id
	 */
	protected function handle_restore( int $post_id ): void {
		if ( ! in_array( get_post_type( $post_id ), PostShareControl::POST_TYPES, true ) ) {
			return;
		}

		delete_post_meta( $post_id, PostShareControl::SHARE_META_KEY );
		delete_post_meta( $post_id, LocalPostService::RESULT_META_KEY );
	}
}
