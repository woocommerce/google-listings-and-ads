<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Exception;
use Throwable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostPublishTrigger
 *
 * Sends a post to Google Business Profile once it's published -- manually or via WordPress's own
 * scheduler -- at most once per publish action. Saving the post again retries a send that failed.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostPublishTrigger implements Service, Registerable {

	/** @var LocalPostService */
	private $local_post_service;

	/** @var LocationService */
	private $location_service;

	/** @var array<int,bool> Post IDs already sent to Google during the current request. */
	private $sent_in_request = [];

	/**
	 * PostPublishTrigger constructor.
	 *
	 * @param LocalPostService $local_post_service
	 * @param LocationService  $location_service
	 */
	public function __construct( LocalPostService $local_post_service, LocationService $location_service ) {
		$this->local_post_service = $local_post_service;
		$this->location_service   = $location_service;
	}

	/**
	 * Register the publish hook.
	 */
	public function register(): void {
		// Fires after meta is saved, so a share toggle saved in the same request as the publish
		// is readable here.
		add_action( 'wp_after_insert_post', [ $this, 'publish_to_business_profile' ], 10, 4 );
	}

	/**
	 * Filter to a post that has genuinely just become published, or an already-published post
	 * saved again after its last send failed.
	 *
	 * @param int          $post_id
	 * @param WP_Post      $post
	 * @param bool         $update
	 * @param WP_Post|null $post_before
	 */
	public function publish_to_business_profile( $post_id, $post, $update, $post_before ): void {
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			return;
		}

		if ( $post_before instanceof WP_Post && 'publish' === $post_before->post_status && ! $this->is_retry( $post ) ) {
			return;
		}

		$this->handle_publish( $post );
	}

	/**
	 * Whether saving an already-published post retries its last send, because that send failed.
	 *
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	protected function is_retry( WP_Post $post ): bool {
		// The block editor follows its own save with a second one for other plugins' meta boxes.
		// Retrying there too would send twice for one save.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checks which request this is.
		if ( isset( $_GET['meta-box-loader'] ) ) {
			return false;
		}

		$last = $this->local_post_service->get_sync_outcome( $post->ID );

		return null !== $last && in_array( $last['outcome'], SyncOutcome::FAILURES, true );
	}

	/**
	 * Send the post to Google Business Profile, when a location is connected, it's a supported
	 * post type with sharing switched on, it isn't already shared, and it hasn't already been
	 * sent during this request.
	 *
	 * @param WP_Post $post
	 */
	protected function handle_publish( WP_Post $post ): void {
		if ( null === $this->location_service->get_selected_location() ) {
			return;
		}

		if ( ! in_array( $post->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		if ( ! (bool) get_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true ) ) {
			return;
		}

		if ( $this->local_post_service->get_share_status( $post->ID )['is_shared'] ) {
			return;
		}

		if ( isset( $this->sent_in_request[ $post->ID ] ) ) {
			return;
		}
		$this->sent_in_request[ $post->ID ] = true;

		try {
			// TODO: move this to a background job -- a synchronous call here delays the editor's
			// Publish response, and holds up the WP-Cron run for a scheduled post.
			$this->local_post_service->create( $post );
			$this->local_post_service->set_sync_outcome( $post->ID, SyncOutcome::SUCCESS );
		} catch ( Throwable $failure ) {
			// Catch every failure, not only exceptions, so the post's own publish is never broken by Google.
			$this->record_failure( $post, $failure );
		}
	}

	/**
	 * Store a failed send's outcome for the editor, and report the failure.
	 *
	 * @param WP_Post   $post
	 * @param Throwable $failure
	 */
	protected function record_failure( WP_Post $post, Throwable $failure ): void {
		$result = SyncOutcome::for_failure( $failure );
		$this->local_post_service->set_sync_outcome( $post->ID, $result['outcome'], $result['message'] );

		// Callbacks on the exception action may expect an Exception, so report an Error by message.
		if ( $failure instanceof Exception ) {
			do_action( 'woocommerce_gla_exception', $failure, __METHOD__ );
		} else {
			do_action( 'woocommerce_gla_error', $failure->getMessage(), __METHOD__ );
		}
	}
}
