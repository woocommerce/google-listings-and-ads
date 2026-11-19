<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteLocalPost;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\SendLocalPost;
use Exception;
use Throwable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostPublishTrigger
 *
 * Reacts to a published post being saved, based on its current sharing toggle: schedules a send
 * to Google Business Profile when sharing is on (at most once per publish action -- saving again
 * retries a send that failed, or makes the first send for a published post that was never sent,
 * such as when sharing was switched on after publishing), or schedules the removal of a post
 * that's still live on Google when sharing has been switched off.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostPublishTrigger implements Service, Registerable {

	/** @var LocalPostService */
	private $local_post_service;

	/** @var JobRepository */
	private $job_repository;

	/** @var array<int,bool> Post IDs already handled -- sent or scheduled for removal -- during the current request. */
	private $handled_in_request = [];

	/**
	 * PostPublishTrigger constructor.
	 *
	 * @param LocalPostService $local_post_service
	 * @param JobRepository    $job_repository
	 */
	public function __construct( LocalPostService $local_post_service, JobRepository $job_repository ) {
		$this->local_post_service = $local_post_service;
		$this->job_repository     = $job_repository;
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
	 * saved again that has never been sent or whose last send failed.
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

		if ( $this->local_post_service->should_unshare( $post ) ) {
			if ( ! $this->is_meta_box_loader_follow_up() ) {
				$this->handle_unshare( $post );
			}
			return;
		}

		if ( $post_before instanceof WP_Post && 'publish' === $post_before->post_status && ! $this->sends_on_save( $post ) ) {
			return;
		}

		$this->handle_publish( $post );
	}

	/**
	 * Whether this request is the block editor's own follow-up save for other plugins' meta
	 * boxes, which immediately follows its main save of the same post.
	 *
	 * @return bool
	 */
	private function is_meta_box_loader_follow_up(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checks which request this is.
		return isset( $_GET['meta-box-loader'] );
	}

	/**
	 * Whether saving an already-published post sends it, because it has never been sent or its
	 * last send failed. Also clears a pending or failed removal's outcome, since sharing is back on.
	 *
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	private function sends_on_save( WP_Post $post ): bool {
		// Sending there too would send twice for one save.
		if ( $this->is_meta_box_loader_follow_up() ) {
			return false;
		}

		$last = $this->local_post_service->get_sync_outcome( $post->ID );

		if ( null !== $last && in_array( $last['outcome'], SyncOutcome::UNSHARE_OUTCOMES, true ) ) {
			// The post isn't being unshared any more, so staying on Google is what the merchant
			// wants now. A pending removal skips itself when it runs, and a failed one's message
			// no longer applies.
			$this->local_post_service->clear_sync_outcome( $post->ID );
			return false;
		}

		if ( null !== $last && SyncOutcome::QUEUED === $last['outcome'] ) {
			// A queued outcome with no pending or running job is stale -- the job was cancelled,
			// or died before it could store a final outcome, so this save should schedule it
			// again rather than leave the post stuck. can_schedule() also counts an
			// already-running job, so a save made while the job is genuinely still in flight
			// doesn't schedule a duplicate send.
			return $this->job_repository->get( SendLocalPost::class )->can_schedule( [ [ $post->ID ] ] );
		}

		// A send Google accepted without a post ID still records an outcome, so it isn't resent.
		return null === $last || in_array( $last['outcome'], SyncOutcome::FAILURES, true );
	}

	/**
	 * Schedule the post to be sent to Google Business Profile, when it's shareable, it hasn't
	 * already reached Google, and it hasn't already been scheduled during this request.
	 *
	 * @param WP_Post $post
	 */
	protected function handle_publish( WP_Post $post ): void {
		if ( ! $this->local_post_service->is_shareable( $post ) ) {
			return;
		}

		if ( $this->local_post_service->get_share_status( $post->ID )['is_shared'] ) {
			return;
		}

		$last = $this->local_post_service->get_sync_outcome( $post->ID );

		if ( null !== $last && SyncOutcome::QUEUED !== $last['outcome'] && ! in_array( $last['outcome'], SyncOutcome::FAILURES, true ) ) {
			// Already reached Google without a stored result -- accepted with no usable post ID,
			// say -- so sending again would create a duplicate. sends_on_save() already gives a
			// resave this same protection; this covers the path that skips it, a status
			// round-trip (unpublish then republish) landing here directly.
			return;
		}

		if ( isset( $this->handled_in_request[ $post->ID ] ) ) {
			return;
		}
		$this->handled_in_request[ $post->ID ] = true;

		if ( $this->local_post_service->has_empty_summary( $post->ID ) ) {
			// Google rejects a post with no text, so don't send one -- the post still publishes;
			// only the send to Google is skipped. sends_on_save() already retries this once the
			// merchant adds text and updates, the same way it retries any other FAILURES outcome.
			$this->local_post_service->set_sync_outcome( $post->ID, SyncOutcome::EMPTY_SUMMARY );
			return;
		}

		$this->local_post_service->set_sync_outcome( $post->ID, SyncOutcome::QUEUED );

		try {
			$this->job_repository->get( SendLocalPost::class )->schedule( [ [ $post->ID ] ] );
		} catch ( Throwable $failure ) {
			// The post's own publish must never break on scheduling. The queued outcome stays
			// behind with no job, so the next save schedules it again.
			if ( $failure instanceof Exception ) {
				do_action( 'woocommerce_gla_exception', $failure, __METHOD__ );
			} else {
				do_action( 'woocommerce_gla_error', $failure->getMessage(), __METHOD__ );
			}
		}
	}

	/**
	 * Schedule the removal of a post that's still live on Google, because sharing has been
	 * switched off, unless it's already been scheduled during this request.
	 *
	 * @param WP_Post $post
	 */
	protected function handle_unshare( WP_Post $post ): void {
		if ( isset( $this->handled_in_request[ $post->ID ] ) ) {
			return;
		}
		$this->handled_in_request[ $post->ID ] = true;

		$this->local_post_service->set_sync_outcome( $post->ID, SyncOutcome::UNSHARE_QUEUED );

		try {
			$result = $this->local_post_service->get_stored_result( $post->ID );
			$this->job_repository->get( DeleteLocalPost::class )->schedule( [ $post->ID, $result ] );
		} catch ( Throwable $failure ) {
			// The post's own save must never break on scheduling. Sharing stays off, so the next
			// save schedules the removal again.
			if ( $failure instanceof Exception ) {
				do_action( 'woocommerce_gla_exception', $failure, __METHOD__ );
			} else {
				do_action( 'woocommerce_gla_error', $failure->getMessage(), __METHOD__ );
			}
		}
	}
}
