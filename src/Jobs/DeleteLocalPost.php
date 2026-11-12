<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteLocalPost
 *
 * Deletes a WordPress post's Google Business Profile local post in the background.
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
	 * Delete the post's Google Business Profile local post -- the same call the trigger used to
	 * make inline. Any failure propagates uncaught: {@see LocalPostService::delete()} already
	 * reports it via `woocommerce_gla_exception` before rethrowing, and letting it keep
	 * propagating lets ActionScheduler's own reschedule-and-log see it too.
	 *
	 * @param array $args The WordPress post ID and its captured stored result.
	 */
	public function process_items( $args ) {
		[ $post_id, $result ] = $args;

		$this->local_post_service->delete( $post_id, $result );
	}
}
