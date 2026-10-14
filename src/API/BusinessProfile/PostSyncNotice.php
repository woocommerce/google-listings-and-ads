<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostSyncNotice
 *
 * Tells the merchant how a share to Google Business Profile went, after the save that attempted
 * it. The block editor reads the notice from its save response; the Classic Editor shows it once on
 * the edit screen it's redirected to. Neither shows it again on reload.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class PostSyncNotice implements Service, Registerable {

	/** @var string REST field holding the notice for a share attempted during the current request. */
	public const FIELD_NAME = 'gla_gbp_sync_notice';

	/** @var string Prefix of the transient that carries a Classic Editor notice to the next page load. */
	protected const TRANSIENT_PREFIX = 'gla_gbp_sync_notice_';

	/** @var int How long a Classic Editor notice waits for the redirect after its save. */
	protected const TRANSIENT_EXPIRATION = 5 * MINUTE_IN_SECONDS;

	/** @var LocalPostService */
	protected $local_post_service;

	/** @var PostPublishTrigger */
	protected $post_publish_trigger;

	/**
	 * PostSyncNotice constructor.
	 *
	 * @param LocalPostService   $local_post_service
	 * @param PostPublishTrigger $post_publish_trigger
	 */
	public function __construct( LocalPostService $local_post_service, PostPublishTrigger $post_publish_trigger ) {
		$this->local_post_service   = $local_post_service;
		$this->post_publish_trigger = $post_publish_trigger;
	}

	/**
	 * Register the REST field and the Classic Editor notice. The notice is kept after the share
	 * attempt, which runs on the same hook at the default priority.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_fields' ] );
		add_action( 'wp_after_insert_post', [ $this, 'keep_notice_for_next_page_load' ], 20 );
		add_action( 'admin_notices', [ $this, 'render_notice' ] );
	}

	/**
	 * Expose the notice for a share attempted during this request on the edit-context post
	 * response, so the block editor gets it from its own save.
	 */
	public function register_rest_fields(): void {
		register_rest_field(
			LocalPostService::SUPPORTED_POST_TYPES,
			self::FIELD_NAME,
			[
				'get_callback' => function ( array $post ) {
					if ( ! current_user_can( 'edit_post', $post['id'] ) ) {
						return null;
					}

					return $this->get_notice_for_this_request( (int) $post['id'] );
				},
				'schema'       => [
					'description' => __( 'The notice to show for a share to Google Business Profile attempted while saving this post.', 'google-listings-and-ads' ),
					'type'        => [ 'object', 'null' ],
					'context'     => [ 'edit' ],
					'readonly'    => true,
					'properties'  => [
						'status'  => [
							'type' => 'string',
							'enum' => [ SyncNotice::STATUS_ERROR, SyncNotice::STATUS_WARNING ],
						],
						'message' => [ 'type' => 'string' ],
					],
				],
			]
		);
	}

	/**
	 * After a Classic Editor save that attempted a share, keep its notice for the edit screen the
	 * merchant is redirected to. The block editor saves over REST and reads the field instead.
	 *
	 * @param int|mixed $post_id
	 */
	public function keep_notice_for_next_page_load( $post_id ): void {
		$post_id = absint( $post_id );
		$user_id = get_current_user_id();

		if ( 0 === $user_id || wp_is_serving_rest_request() ) {
			return;
		}

		$notice = $this->get_notice_for_this_request( $post_id );

		if ( null === $notice ) {
			return;
		}

		set_transient( $this->get_transient_name( $user_id, $post_id ), $notice, self::TRANSIENT_EXPIRATION );
	}

	/**
	 * Show a kept notice on the post's edit screen, once.
	 */
	public function render_notice(): void {
		$post = $this->get_edited_post();

		if ( null === $post ) {
			return;
		}

		$transient_name = $this->get_transient_name( get_current_user_id(), $post->ID );
		$notice         = get_transient( $transient_name );

		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		delete_transient( $transient_name );

		$status = SyncNotice::STATUS_WARNING === ( $notice['status'] ?? '' ) ? SyncNotice::STATUS_WARNING : SyncNotice::STATUS_ERROR;

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $status ),
			esc_html( $notice['message'] )
		);
	}

	/**
	 * Get the notice for a share attempted during this request.
	 *
	 * @param int $post_id
	 *
	 * @return array|null Shaped `{ status, message }`, or null when no share was attempted or there's
	 *                    nothing to tell the merchant.
	 */
	protected function get_notice_for_this_request( int $post_id ): ?array {
		if ( ! $this->post_publish_trigger->was_sent_in_request( $post_id ) ) {
			return null;
		}

		return SyncNotice::for_outcome( $this->local_post_service->get_sync_outcome( $post_id ) );
	}

	/**
	 * Get the post being edited on the current edit screen, when it's a supported post type.
	 *
	 * @return WP_Post|null
	 */
	protected function get_edited_post(): ?WP_Post {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
			return null;
		}

		$post = get_post();

		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * Get the name of the transient holding a user's notice for a post.
	 *
	 * @param int $user_id
	 * @param int $post_id
	 *
	 * @return string
	 */
	protected function get_transient_name( int $user_id, int $post_id ): string {
		return self::TRANSIENT_PREFIX . "{$user_id}_{$post_id}";
	}
}
