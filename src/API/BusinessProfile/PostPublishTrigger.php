<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\SendLocalPost;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostPublishTrigger
 *
 * Schedules a post to be sent to Google Business Profile once it's published -- manually or via
 * WordPress's own scheduler -- at most once per publish action. Saving the post again retries a
 * send that failed, or makes the first send for a published post that was never sent, such as
 * when sharing was switched on after publishing.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostPublishTrigger implements Service, Registerable {

	/** @var LocalPostService */
	private $local_post_service;

	/** @var JobRepository */
	private $job_repository;

	/** @var array<int,bool> Post IDs already scheduled to send during the current request. */
	private $sent_in_request = [];

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

		if ( $post_before instanceof WP_Post && 'publish' === $post_before->post_status && ! $this->sends_on_save( $post ) ) {
			return;
		}

		$this->handle_publish( $post );
	}

	/**
	 * Whether saving an already-published post sends it, because it has never been sent or its
	 * last send failed.
	 *
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	private function sends_on_save( WP_Post $post ): bool {
		// The block editor follows its own save with a second one for other plugins' meta boxes.
		// Sending there too would send twice for one save.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checks which request this is.
		if ( isset( $_GET['meta-box-loader'] ) ) {
			return false;
		}

		$last = $this->local_post_service->get_sync_outcome( $post->ID );

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
	 * Schedule the post to be sent to Google Business Profile, when it's shareable, it isn't
	 * already shared, and it hasn't already been scheduled during this request.
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

		if ( isset( $this->sent_in_request[ $post->ID ] ) ) {
			return;
		}
		$this->sent_in_request[ $post->ID ] = true;

		$this->local_post_service->set_sync_outcome( $post->ID, SyncOutcome::QUEUED );
		$this->job_repository->get( SendLocalPost::class )->schedule( [ [ $post->ID ] ] );
	}
}
