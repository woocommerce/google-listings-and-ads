<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\MissingLocalPostIdException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Exception;
use Throwable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class SendLocalPost
 *
 * Sends a WordPress post to Google Business Profile in the background. Re-checks the post still
 * qualifies before sending it, since state can change between scheduling and running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class SendLocalPost extends AbstractActionSchedulerJob {

	/** @var string Tracked send type for a `localPosts.create` request. */
	private const SYNC_TYPE_CREATE = 'create';

	/** @var LocalPostService */
	protected $local_post_service;

	/** @var LocationService */
	protected $location_service;

	/**
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param LocalPostService          $local_post_service
	 * @param LocationService           $location_service
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		LocalPostService $local_post_service,
		LocationService $location_service
	) {
		parent::__construct( $action_scheduler, $monitor );
		$this->local_post_service = $local_post_service;
		$this->location_service   = $location_service;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'send_local_post';
	}

	/**
	 * Schedule a post to be sent, unless it's already queued.
	 *
	 * @param array $args WordPress post IDs to send, as `[ [ $post_id, ... ] ]`.
	 *
	 * @throws JobException If no post ID is provided as argument.
	 */
	public function schedule( array $args = [] ) {
		$post_ids = array_filter( $args[0] ?? [], 'is_int' );

		if ( empty( $post_ids ) ) {
			throw JobException::item_not_provided( 'WordPress Post ID' );
		}

		if ( $this->can_schedule( [ $post_ids ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $post_ids ] );
		}
	}

	/**
	 * Send each post, after confirming it still qualifies -- state can change between scheduling
	 * and running (sharing switched off, the post trashed, the post sent by another path).
	 *
	 * @param int[] $post_ids
	 */
	public function process_items( $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );

			if ( ! $this->still_qualifies( $post ) ) {
				continue;
			}

			$this->send( $post );
		}
	}

	/**
	 * Whether a post is still a genuine candidate to send -- published, a supported post type,
	 * sharing still switched on, and not already shared.
	 *
	 * @param WP_Post|null $post
	 *
	 * @return bool
	 */
	private function still_qualifies( ?WP_Post $post ): bool {
		if ( null === $post || 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! in_array( $post->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return false;
		}

		if ( ! (bool) get_post_meta( $post->ID, PostShareControl::SHARE_META_KEY, true ) ) {
			return false;
		}

		if ( $this->local_post_service->get_share_status( $post->ID )['is_shared'] ) {
			return false;
		}

		return null !== $this->location_service->get_selected_location();
	}

	/**
	 * Send a post to Google Business Profile and store the outcome.
	 *
	 * @param WP_Post $post
	 */
	private function send( WP_Post $post ): void {
		try {
			$this->local_post_service->create( $post );
		} catch ( MissingLocalPostIdException $exception ) {
			// Google accepted the post, so a retry would only create a duplicate. With no post ID
			// there's no stored result, so the share status stays "not shared": success with no
			// link is a valid state here, not one to correct.
			$this->local_post_service->set_sync_outcome( $post->ID, $exception->get_outcome(), '', $exception->is_image_omitted() );
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		} catch ( Throwable $failure ) {
			$this->record_failure( $post, $failure );
			return;
		}

		// Outside the try, so a failing callback can't turn an accepted post into a failed one.
		$this->report_sync_success();
	}

	/**
	 * Store a failed send's outcome, and report the failure.
	 *
	 * @param WP_Post   $post
	 * @param Throwable $failure
	 */
	private function record_failure( WP_Post $post, Throwable $failure ): void {
		$result = SyncOutcome::for_failure( $failure );
		$this->local_post_service->set_sync_outcome( $post->ID, $result['outcome'], $result['message'] );

		$this->report_sync_failure( $failure );

		// Callbacks on the exception action may expect an Exception, so report an Error by message.
		if ( $failure instanceof Exception ) {
			do_action( 'woocommerce_gla_exception', $failure, __METHOD__ );
		} else {
			do_action( 'woocommerce_gla_error', $failure->getMessage(), __METHOD__ );
		}
	}

	/**
	 * Report that Google accepted a post.
	 */
	private function report_sync_success(): void {
		try {
			/**
			 * Fires when Google Business Profile accepts a post.
			 *
			 * @param array $properties {
			 *     @type string $type The kind of send: `create` when the post is sent to Google for the first time.
			 * }
			 */
			do_action( 'woocommerce_gla_business_profile_sync_success', [ 'type' => self::SYNC_TYPE_CREATE ] );
		} catch ( Throwable $error ) {
			$this->report_listener_error( 'woocommerce_gla_business_profile_sync_success', $error );
		}
	}

	/**
	 * Report that sending a post to Google failed.
	 *
	 * @param Throwable $failure
	 */
	private function report_sync_failure( Throwable $failure ): void {
		try {
			/**
			 * Fires when sending a post to Google Business Profile fails.
			 *
			 * @param array $properties {
			 *     @type string $type   The kind of send: `create` when the post is sent to Google for the first time.
			 *     @type string $reason Google's error status, such as `INVALID_ARGUMENT`. Empty when Google gave none.
			 * }
			 */
			do_action(
				'woocommerce_gla_business_profile_sync_failure',
				[
					'type'   => self::SYNC_TYPE_CREATE,
					'reason' => self::get_google_error_status( $failure ),
				]
			);
		} catch ( Throwable $error ) {
			$this->report_listener_error( 'woocommerce_gla_business_profile_sync_failure', $error );
		}
	}

	/**
	 * Report a sync hook callback that threw, so it can't break the job.
	 *
	 * @param string    $hook  The hook whose callback threw.
	 * @param Throwable $error
	 */
	private function report_listener_error( string $hook, Throwable $error ): void {
		do_action( 'woocommerce_gla_error', sprintf( 'A %s callback threw: %s', $hook, $error->getMessage() ), __METHOD__ );
	}

	/**
	 * Get Google's error status from a failed send, such as `INVALID_ARGUMENT`.
	 *
	 * @param Throwable $failure
	 *
	 * @return string Empty when the failure carries no error from Google, such as a network failure.
	 */
	private static function get_google_error_status( Throwable $failure ): string {
		if ( ! $failure instanceof BusinessProfileApiException ) {
			return '';
		}

		$status = $failure->get_response_body()['error']['status'] ?? '';

		return is_string( $status ) ? $status : '';
	}
}
