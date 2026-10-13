<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Exception;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostPublishTrigger
 *
 * Sends a post to Google Business Profile the moment it genuinely transitions into `publish` --
 * manually or via WordPress's own scheduler -- at most once per publish action.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostPublishTrigger implements Service, Registerable {

	/** @var LocalPostService */
	private $local_post_service;

	/** @var array<int,bool> Post IDs already sent to Google during the current request. */
	private $sent_in_request = [];

	/**
	 * PostPublishTrigger constructor.
	 *
	 * @param LocalPostService $local_post_service
	 */
	public function __construct( LocalPostService $local_post_service ) {
		$this->local_post_service = $local_post_service;
	}

	/**
	 * Register the publish-transition hook.
	 */
	public function register(): void {
		// transition_post_status fires for both a manual publish and WP-Cron's scheduled
		// auto-publish, so one hook covers both without a separate code path.
		add_action( 'transition_post_status', [ $this, 'handle_transition_post_status' ], 10, 3 );
	}

	/**
	 * Filter to a genuine transition into `publish`, excluding every other status and excluding a
	 * publish-to-publish update (a save on an already-published post is not a publish action).
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function handle_transition_post_status( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		$this->handle_publish( $post );
	}

	/**
	 * Send the post to Google Business Profile, when it's a supported post type with sharing
	 * switched on, and it hasn't already been sent during this request.
	 *
	 * @param WP_Post $post
	 */
	protected function handle_publish( WP_Post $post ): void {
		if ( ! in_array( $post->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		if ( ! (bool) get_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true ) ) {
			return;
		}

		if ( isset( $this->sent_in_request[ $post->ID ] ) ) {
			return;
		}
		$this->sent_in_request[ $post->ID ] = true;

		try {
			$this->local_post_service->create( $post );
		} catch ( Exception $exception ) {
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		}
	}
}
