<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteLocalPost
 *
 * Deletes a WordPress post's Google Business Profile local post in the background, re-checking it
 * should still be removed before doing so. Records a failure as the post's sync outcome so the
 * editor can tell the merchant it's still on Google, instead of propagating it uncaught.
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
	 */
	public function process_items( array $args ) {
		[ $post_id, $result ] = $args;

		if ( ! $this->local_post_service->should_remove_from_business_profile( $post_id ) ) {
			return;
		}

		try {
			$this->local_post_service->delete( $post_id, $result );
		} catch ( Throwable $failure ) {
			$this->record_failure( $post_id, $failure );
			return;
		}

		// A prior attempt may have failed and left a stored outcome behind for the editor to show.
		$this->local_post_service->clear_sync_outcome( $post_id );
	}

	/**
	 * Store a failed removal's outcome, so the editor keeps showing the post as shared, with a
	 * message, rather than leave the merchant thinking it's gone when it's still on Google.
	 *
	 * @param int       $post_id
	 * @param Throwable $failure
	 */
	private function record_failure( int $post_id, Throwable $failure ): void {
		$message = SyncOutcome::for_failure( $failure )['message'];
		$this->local_post_service->set_sync_outcome( $post_id, SyncOutcome::UNSHARE_FAILURE, $message );

		try {
			// Callbacks on the exception action may expect an Exception, so report an Error by message.
			if ( $failure instanceof Exception ) {
				do_action( 'woocommerce_gla_exception', $failure, __METHOD__ );
			} else {
				do_action( 'woocommerce_gla_error', $failure->getMessage(), __METHOD__ );
			}
		} catch ( Throwable $error ) {
			// A throwing listener here must not escape and reschedule the batch -- the removal
			// already failed, so replaying it would only retry it a second, redundant time.
			$this->report_listener_error( $failure instanceof Exception ? 'woocommerce_gla_exception' : 'woocommerce_gla_error', $error );
		}
	}
}
