<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocalPostService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\PostShareControl;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncNotice;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\SyncOutcome;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_Post;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPostController
 *
 * Lets the post editor check whether a post shared to Google Business Profile still exists on
 * Google, and unlinks the post when someone deleted it there.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile
 */
class LocalPostController extends BaseController {

	/** @var string Why a check failed: the Google account or the Jetpack connection needs reconnecting. */
	public const CHECK_FAILURE_RECONNECT = 'reconnect';

	/** @var string Why a check failed: any other failure. */
	public const CHECK_FAILURE_ERROR = 'error';

	/** @var LocalPostService */
	protected $local_post_service;

	/** @var LocationService */
	protected $location_service;

	/**
	 * LocalPostController constructor.
	 *
	 * @param RESTServer       $server
	 * @param LocalPostService $local_post_service
	 * @param LocationService  $location_service
	 */
	public function __construct( RESTServer $server, LocalPostService $local_post_service, LocationService $location_service ) {
		parent::__construct( $server );

		$this->local_post_service = $local_post_service;
		$this->location_service   = $location_service;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'business-profile/posts/(?P<post_id>[\d]+)/check',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_check_callback(),
					'permission_callback' => $this->get_edit_post_permission_callback(),
					'args'                => $this->get_check_args(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback for checking a shared post on Google.
	 *
	 * Google is only asked when a location is connected, the post has a stored link, and no
	 * send or removal is waiting to run, since the job about to run will change the link anyway.
	 *
	 * @return callable
	 */
	protected function get_check_callback(): callable {
		return function ( Request $request ) {
			$post_id = (int) $request['post_id'];
			$post    = get_post( $post_id );

			if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, LocalPostService::SUPPORTED_POST_TYPES, true ) ) {
				return new Response(
					[ 'message' => __( 'This post can’t be shared to Google Business Profile.', 'google-listings-and-ads' ) ],
					404
				);
			}

			$result = $this->local_post_service->get_stored_result( $post_id );

			if ( ! $this->should_check( $post_id, $result ) ) {
				return $this->get_share_state( $post_id, false, null );
			}

			try {
				if ( ! $this->local_post_service->exists_on_google( $result ) ) {
					$this->local_post_service->unlink_deleted_post( $post_id, (string) $result['google_post_id'] );
				}
			} catch ( Exception $exception ) {
				do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );

				$failure = $exception instanceof AccountReconnect ? self::CHECK_FAILURE_RECONNECT : self::CHECK_FAILURE_ERROR;

				return $this->get_share_state( $post_id, false, $failure );
			}

			return $this->get_share_state( $post_id, true, null );
		};
	}

	/**
	 * Get the permission callback: the user must be able to edit this specific post, the same
	 * check the post's own share fields use.
	 *
	 * @return callable
	 */
	protected function get_edit_post_permission_callback(): callable {
		return function ( Request $request ) {
			return current_user_can( 'edit_post', (int) $request['post_id'] );
		};
	}

	/**
	 * Whether Google should be asked about a post.
	 *
	 * @param int   $post_id
	 * @param array $result The post's stored result.
	 *
	 * @return bool
	 */
	protected function should_check( int $post_id, array $result ): bool {
		if ( null === $this->location_service->get_selected_location() ) {
			return false;
		}

		if ( ! LocalPostService::has_complete_result( $result ) ) {
			return false;
		}

		$outcome = $this->local_post_service->get_sync_outcome( $post_id )['outcome'] ?? null;

		return SyncOutcome::QUEUED !== $outcome && SyncOutcome::UNSHARE_QUEUED !== $outcome;
	}

	/**
	 * Get the post's share state after a check, in the same shapes as its REST fields.
	 *
	 * @param int         $post_id
	 * @param bool        $checked Whether Google answered the check.
	 * @param string|null $check_failure Why the check failed, or null when it didn't.
	 *
	 * @return array
	 */
	protected function get_share_state( int $post_id, bool $checked, ?string $check_failure ): array {
		return [
			'checked'          => $checked,
			'check_failure'    => $check_failure,
			'is_share_enabled' => (bool) get_post_meta( $post_id, PostShareControl::SHARE_META_KEY, true ),
			'share_status'     => $this->local_post_service->get_share_status( $post_id ),
			'sync_outcome'     => $this->local_post_service->get_sync_outcome_for_display( $post_id ),
		];
	}

	/**
	 * Get the arguments for the check.
	 *
	 * @return array
	 */
	protected function get_check_args(): array {
		return [
			'post_id' => [
				'type'        => 'integer',
				'description' => __( 'The ID of the post to check.', 'google-listings-and-ads' ),
				'required'    => true,
			],
		];
	}

	/**
	 * Get the schema for the check's response.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'checked'          => [
				'type'        => 'boolean',
				'description' => __( 'Whether Google answered the check. False when the check was skipped or failed.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'check_failure'    => [
				'type'        => [ 'string', 'null' ],
				'description' => __( 'Why the check failed, or null when it didn’t.', 'google-listings-and-ads' ),
				'enum'        => [ self::CHECK_FAILURE_RECONNECT, self::CHECK_FAILURE_ERROR, null ],
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'is_share_enabled' => [
				'type'        => 'boolean',
				'description' => __( 'Whether sharing to Google Business Profile is turned on for the post.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'share_status'     => [
				'type'        => 'object',
				'description' => __( 'Whether the post has been shared to Google Business Profile, and the link to view it once Google provides one.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'properties'  => [
					'is_shared'  => [ 'type' => 'boolean' ],
					'search_url' => [ 'type' => 'string' ],
				],
			],
			'sync_outcome'     => [
				'type'        => [ 'object', 'null' ],
				'description' => __( 'The outcome of the last attempt to share the post to Google Business Profile, with a ready-to-display message and status for a non-success outcome.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'properties'  => [
					'outcome'         => [
						'type' => 'string',
						'enum' => SyncOutcome::ALL,
					],
					'message'         => [ 'type' => 'string' ],
					'image_omitted'   => [ 'type' => 'boolean' ],
					'display_message' => [ 'type' => [ 'string', 'null' ] ],
					'display_status'  => [
						'type' => [ 'string', 'null' ],
						'enum' => [ SyncNotice::STATUS_ERROR, SyncNotice::STATUS_WARNING, null ],
					],
				],
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'business_profile_local_post_check';
	}
}
