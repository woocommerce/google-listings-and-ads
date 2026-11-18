<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Exception;
use Throwable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteLocalPost
 *
 * Deletes a WordPress post's Google Business Profile local post in the background, re-checking it
 * should still be removed before doing so. A failure to remove a still-published post, whose
 * sharing was turned off, is recorded as its sync outcome so the editor can tell the merchant it's
 * still on Google. A failure for a trashed or deleted post propagates, so Action Scheduler retries it.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class DeleteLocalPost extends AbstractActionSchedulerJob {

	/** @var LocalPostService */
	protected $local_post_service;

	/**
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param LocalPostService          $local_post_service
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		LocalPostService $local_post_service
	) {
		parent::__construct( $action_scheduler, $monitor );
		$this->local_post_service = $local_post_service;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'delete_local_post';
	}

	/**
	 * Schedule a post's Google Business Profile local post to be deleted, unless it's already
	 * queued.
	 *
	 * @param array $args The WordPress post ID and its captured stored result, as `[ $post_id, $result ]`.
	 *
	 * @throws JobException If the post ID or result isn't provided as argument.
	 */
	public function schedule( array $args = [] ) {
		$post_id = $args[0] ?? null;
		$result  = $args[1] ?? null;

		if ( ! is_int( $post_id ) || ! is_array( $result ) ) {
			throw JobException::item_not_provided( 'WordPress Post ID and its stored Business Profile result' );
		}

		if ( $this->can_schedule( [ [ $post_id, $result ] ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ [ $post_id, $result ] ] );
		}
	}

	/**
	 * Delete the post's Google Business Profile local post, after confirming it should still be
	 * removed -- sharing can be switched back on between scheduling and running, for a post this
	 * delete was scheduled for because its toggle was off, not because it was trashed or deleted.
	 *
	 * @param array $args The WordPress post ID and its captured stored result.
	 *
	 * @throws Throwable When removing a trashed or deleted post fails, so Action Scheduler retries it.
	 */
	public function process_items( array $args ) {
		[ $post_id, $result ] = $args;

		if ( ! $this->local_post_service->should_remove_from_business_profile( $post_id ) ) {
			return;
		}

		try {
			$this->local_post_service->delete( $post_id, $result );
		} catch ( Throwable $failure ) {
			// Only a still-published post has an editor to show the failure. A trashed or deleted
			// post has none, so its failure is left to Action Scheduler's retry instead.
			if ( ! $this->is_published( $post_id ) ) {
				throw $failure;
			}

			$this->record_failure( $post_id, $failure );
			return;
		}

		// A prior attempt may have failed and left a stored outcome behind for the editor to show.
		$this->local_post_service->clear_sync_outcome( $post_id );
	}

	/**
	 * Whether the post still exists and is published, which means its removal was scheduled
	 * because sharing was turned off.
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function is_published( int $post_id ): bool {
		$post = get_post( $post_id );

		return $post instanceof WP_Post && 'publish' === $post->post_status;
	}

	/**
	 * Store a failed removal's outcome, so the editor keeps showing the post as shared, with a
	 * message, rather than leave the merchant thinking it's gone when it's still on Google.
	 *
	 * @param int       $post_id
	 * @param Throwable $failure
	 */
	private function record_failure( int $post_id, Throwable $failure ): void {
		$result = SyncOutcome::for_failure( $failure );
		// A lost Google grant needs reconnecting, not just a resave -- keep that distinct from
		// the generic removal failure instead of collapsing it to the same, less useful message.
		$outcome = SyncOutcome::ACCOUNT_DISCONNECTED === $result['outcome']
			? SyncOutcome::UNSHARE_ACCOUNT_DISCONNECTED
			: SyncOutcome::UNSHARE_FAILURE;
		$this->local_post_service->set_sync_outcome( $post_id, $outcome, $result['message'] );

		// LocalPostService::delete() already reports an Exception before rethrowing it. Only an
		// Error escapes it unreported.
		if ( $failure instanceof Exception ) {
			return;
		}

		try {
			do_action( 'woocommerce_gla_error', $failure->getMessage(), __METHOD__ );
		} catch ( Throwable $error ) {
			// A throwing listener here must not escape and reschedule the batch -- the removal
			// already failed, so replaying it would only retry it a second, redundant time.
			$this->report_listener_error( 'woocommerce_gla_error', $error );
		}
	}
}
