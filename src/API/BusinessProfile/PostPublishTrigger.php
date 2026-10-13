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
 * Sends a post to Google Business Profile once it's published -- manually or via WordPress's own
 * scheduler -- at most once per publish action.
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
		// wp_after_insert_post fires once meta and terms are already saved, on all three publish
		// paths (REST create, REST update, and WP-Cron's scheduled wp_publish_post()) -- unlike
		// transition_post_status, which fires before a share toggle saved in the same request is
		// readable, so "turn sharing on and publish in one step" would otherwise send nothing.
		add_action( 'wp_after_insert_post', [ $this, 'publish_to_business_profile' ], 10, 4 );
	}

	/**
	 * Filter to a post that has genuinely just become published. Hook callback parameters are
	 * left untyped and validated here, per this repo's own convention for data arriving through a
	 * hook.
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

		if ( $post_before instanceof WP_Post && 'publish' === $post_before->post_status ) {
			return;
		}

		$this->handle_publish( $post );
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
			// Publish response, and holds up the WP-Cron run for a scheduled post. Retrying a
			// failed send is a separate ticket's scope.
			$this->local_post_service->create( $post );
		} catch ( Exception $exception ) {
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		}
	}
}
