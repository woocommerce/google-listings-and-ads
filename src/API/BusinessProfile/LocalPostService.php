<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Exception;
use InvalidArgumentException;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocalPostService
 *
 * Builds and sends the `localPosts.create` and `localPosts.delete` requests for a WordPress
 * post, and stores or clears the result as post meta. The Summary text is read from post meta
 * and never written here; callers decide when to invoke {@see self::create()} or
 * {@see self::delete()}.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class LocalPostService implements Service, OptionsAwareInterface, Registerable {

	use OptionsAwareTrait;

	/** @var string Post meta key holding the merchant-edited Summary text. FE-written, read-only here. */
	public const SUMMARY_META_KEY = '_wc_gla_gbp_summary';

	/** @var string Post meta key holding the account/location/post-ID triple and searchUrl from a successful create. */
	public const RESULT_META_KEY = '_wc_gla_gbp_post';

	/** @var string Post meta key holding the outcome of the post's last attempt to reach Google, and Google's own message. */
	public const SYNC_OUTCOME_META_KEY = '_wc_gla_gbp_sync_outcome';

	/** @var string REST field exposing whether a post has been shared, and its searchUrl once Google provides one. */
	public const SHARE_STATUS_FIELD_NAME = 'gla_gbp_share_status';

	/** @var string REST field exposing the outcome of the post's last attempt to reach Google. */
	public const SYNC_OUTCOME_FIELD_NAME = 'gla_gbp_sync_outcome';

	/** @var string[] Post types this feature is available on. A config change, not a rebuild, to add one. */
	public const SUPPORTED_POST_TYPES = [ 'post' ];

	/** @var int The real `summary` character limit, confirmed by Google. */
	public const SUMMARY_CHAR_LIMIT = 1500;

	/** @var BusinessProfileApiClient */
	private $client;

	/** @var WP */
	private $wp;

	/** @var ISOUtility */
	private $iso_utility;

	/** @var LocalPostImageService */
	private $image_service;

	/** @var LocationService */
	private $location_service;

	/** @var WPML */
	private $wpml;

	/**
	 * LocalPostService constructor.
	 *
	 * @param BusinessProfileApiClient $client
	 * @param WP                       $wp
	 * @param ISOUtility               $iso_utility
	 * @param LocalPostImageService    $image_service
	 * @param LocationService          $location_service
	 * @param WPML                     $wpml
	 */
	public function __construct( BusinessProfileApiClient $client, WP $wp, ISOUtility $iso_utility, LocalPostImageService $image_service, LocationService $location_service, WPML $wpml ) {
		$this->client           = $client;
		$this->wp               = $wp;
		$this->iso_utility      = $iso_utility;
		$this->image_service    = $image_service;
		$this->location_service = $location_service;
		$this->wpml             = $wpml;
	}

	/**
	 * Register this feature's post-meta schema and REST field.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_fields' ] );
	}

	/**
	 * Register the Summary, result, and sync outcome post-meta keys.
	 */
	public function register_post_meta(): void {
		foreach ( self::SUPPORTED_POST_TYPES as $post_type ) {
			register_post_meta(
				$post_type,
				self::SUMMARY_META_KEY,
				[
					'single'            => true,
					'type'              => 'string',
					// An edit-context collection request only checks the generic `edit_posts` capability,
					// so check `edit_post` for this specific post before returning the merchant's text;
					// anyone else gets an empty string.
					'show_in_rest'      => [
						'prepare_callback' => static function ( $value, $request ) {
							$post_id = (int) get_the_ID();

							if ( 'edit' !== $request->get_param( 'context' ) || ! current_user_can( 'edit_post', $post_id ) ) {
								return '';
							}

							return (string) $value;
						},
					],
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				]
			);

			register_post_meta(
				$post_type,
				self::RESULT_META_KEY,
				[
					'single'       => true,
					'type'         => 'object',
					// Not REST-visible: `show_in_rest` has no read-side capability check, so exposing
					// this server-written record would make it world-readable and, via `auth_callback`
					// (which only gates writes), forgeable by any post editor.
					'show_in_rest' => false,
				]
			);

			register_post_meta(
				$post_type,
				self::SYNC_OUTCOME_META_KEY,
				[
					'single'       => true,
					'type'         => 'object',
					// Not REST-visible for the same reason as the result key; the editor reads it
					// through its own read-only REST field instead.
					'show_in_rest' => false,
				]
			);
		}
	}

	/**
	 * Expose a read-only share status on the edit-context post response.
	 */
	public function register_rest_fields(): void {
		register_rest_field(
			self::SUPPORTED_POST_TYPES,
			self::SHARE_STATUS_FIELD_NAME,
			[
				'get_callback' => function ( array $post ) {
					if ( ! current_user_can( 'edit_post', $post['id'] ) ) {
						return null;
					}

					return $this->get_share_status( (int) $post['id'] );
				},
				'schema'       => [
					'description' => __( 'Whether this post has been shared to Google Business Profile, and the link to view it once Google provides one.', 'google-listings-and-ads' ),
					// A collection request only checks the generic `edit_posts` capability, not
					// per post, so this field's own get_callback can still return null for a post
					// its caller can't individually edit.
					'type'        => [ 'object', 'null' ],
					'context'     => [ 'edit' ],
					'readonly'    => true,
					'properties'  => [
						'is_shared'  => [ 'type' => 'boolean' ],
						'search_url' => [ 'type' => 'string' ],
					],
				],
			]
		);

		register_rest_field(
			self::SUPPORTED_POST_TYPES,
			self::SYNC_OUTCOME_FIELD_NAME,
			[
				'get_callback' => function ( array $post ) {
					if ( ! current_user_can( 'edit_post', $post['id'] ) ) {
						return null;
					}

					return $this->get_sync_outcome( (int) $post['id'] );
				},
				'schema'       => [
					'description' => __( 'The outcome of the last attempt to share this post to Google Business Profile, with the message Google returned, if any.', 'google-listings-and-ads' ),
					'type'        => [ 'object', 'null' ],
					'context'     => [ 'edit' ],
					'readonly'    => true,
					'properties'  => [
						'outcome'       => [
							'type' => 'string',
							'enum' => SyncOutcome::ALL,
						],
						'message'       => [ 'type' => 'string' ],
						'image_omitted' => [ 'type' => 'boolean' ],
					],
				],
			]
		);
	}

	/**
	 * Get whether a post has been shared, and its `searchUrl` once Google provides one, from the
	 * stored result.
	 *
	 * @param int $post_id
	 *
	 * @return array Shaped `{ is_shared, search_url }`.
	 */
	public function get_share_status( int $post_id ): array {
		$result         = $this->get_stored_result( $post_id );
		$google_post_id = (string) ( $result['post_id'] ?? '' );

		return [
			'is_shared'  => '' !== $google_post_id,
			'search_url' => (string) ( $result['search_url'] ?? '' ),
		];
	}

	/**
	 * Get the outcome of a post's last attempt to reach Google.
	 *
	 * @param int $post_id
	 *
	 * @return array|null Shaped `{ outcome, message, image_omitted }`, or null when the post has never been sent.
	 */
	public function get_sync_outcome( int $post_id ): ?array {
		$stored  = get_post_meta( $post_id, self::SYNC_OUTCOME_META_KEY, true );
		$outcome = is_array( $stored ) ? (string) ( $stored['outcome'] ?? '' ) : '';

		if ( ! in_array( $outcome, SyncOutcome::ALL, true ) ) {
			return null;
		}

		return [
			'outcome'       => $outcome,
			'message'       => (string) ( $stored['message'] ?? '' ),
			'image_omitted' => (bool) ( $stored['image_omitted'] ?? false ),
		];
	}

	/**
	 * Store the outcome of a post's attempt to reach Google, replacing the previous one.
	 *
	 * @param int    $post_id
	 * @param string $outcome One of the {@see SyncOutcome} values.
	 * @param string $message       Google's own message, stored as returned. Empty when there is none.
	 * @param bool   $image_omitted Whether the post was sent without its featured image, because no version of it was usable.
	 *
	 * @throws InvalidArgumentException When the outcome isn't a {@see SyncOutcome} value.
	 */
	public function set_sync_outcome( int $post_id, string $outcome, string $message = '', bool $image_omitted = false ): void {
		if ( ! in_array( $outcome, SyncOutcome::ALL, true ) ) {
			throw new InvalidArgumentException( "Unknown Google Business Profile sync outcome: {$outcome}" );
		}

		// update_post_meta() unslashes its value, which would alter a backslash in Google's message.
		update_post_meta(
			$post_id,
			self::SYNC_OUTCOME_META_KEY,
			wp_slash(
				[
					'outcome'       => $outcome,
					'message'       => $message,
					'image_omitted' => $image_omitted,
				]
			)
		);
	}

	/**
	 * Build and send a `localPosts.create` request for a WordPress post, and store the
	 * result and a success outcome keyed to that post. The outcome is pending verification
	 * when the connected location was unverified at send time.
	 *
	 * @param WP_Post $post
	 *
	 * @return LocalPost The created post, as returned by Google.
	 * @throws Exception When no Business Profile location is connected.
	 * @throws MissingLocalPostIdException When Google accepts the post but its response carries no post identifier.
	 * @throws BusinessProfileApiException On a non-2xx Business Profile API response.
	 * @throws AccountReconnect When the Jetpack connection or the Google Account needs reconnecting.
	 */
	public function create( WP_Post $post ): LocalPost {
		$connection  = $this->options->get( OptionsInterface::BUSINESS_PROFILE, [] );
		$account_id  = is_array( $connection ) ? (string) ( $connection['account_id'] ?? '' ) : '';
		$location_id = is_array( $connection ) ? (string) ( $connection['location_id'] ?? '' ) : '';

		if ( '' === $account_id || '' === $location_id ) {
			throw new Exception(
				__( 'No Google Business Profile location is connected.', 'google-listings-and-ads' ),
				404
			);
		}

		$image_url     = $this->image_service->get_image_url( $post );
		$image_omitted = has_post_thumbnail( $post ) && '' === $image_url;
		$post_language = $this->wpml->get_post_language( $post->ID );
		$locale        = '' !== $post_language ? $post_language : $this->wp->get_locale();
		$local_post    = new LocalPost(
			self::truncate_summary( (string) get_post_meta( $post->ID, self::SUMMARY_META_KEY, true ) ),
			$this->iso_utility->wp_locale_to_bcp47( $locale ),
			(string) get_permalink( $post ),
			$image_url
		);

		$outcome = SyncOutcome::for_success( $this->location_service->is_location_unverified( $location_id ) );

		$response = $this->client->post(
			"accounts/{$account_id}/locations/{$location_id}/localPosts",
			$local_post->to_array()
		);

		$created    = LocalPost::from_array( $response );
		$created_id = BusinessProfileApiClient::get_resource_id( $created->get_name() ?? '' );

		if ( '' === $created_id ) {
			// Google gives no create-idempotency key, so a record with no post ID is
			// indistinguishable from "never shared" to a caller -- and would let the same
			// WordPress post be sent to Google again, creating a duplicate post.
			throw new MissingLocalPostIdException(
				__( 'Google Business Profile returned no post identifier.', 'google-listings-and-ads' ),
				502,
				$image_omitted,
				$outcome
			);
		}

		update_post_meta(
			$post->ID,
			self::RESULT_META_KEY,
			[
				'account_id'  => $account_id,
				'location_id' => $location_id,
				'post_id'     => $created_id,
				'search_url'  => $created->get_search_url() ?? '',
			]
		);

		$this->set_sync_outcome( $post->ID, $outcome, '', $image_omitted );

		return $created;
	}

	/**
	 * Get a post's stored account/location/post-ID/searchUrl result.
	 *
	 * @param int $post_id
	 *
	 * @return array Empty when nothing has been stored yet.
	 */
	public function get_stored_result( int $post_id ): array {
		$result = get_post_meta( $post_id, self::RESULT_META_KEY, true );

		return is_array( $result ) ? $result : [];
	}

	/**
	 * Whether a stored result carries all three IDs a delete call needs. Shared with
	 * {@see PostDeleteTrigger} so the two checks can't drift apart.
	 *
	 * @param array $result
	 *
	 * @return bool
	 */
	public static function has_complete_result( array $result ): bool {
		return '' !== (string) ( $result['account_id'] ?? '' )
			&& '' !== (string) ( $result['location_id'] ?? '' )
			&& '' !== (string) ( $result['post_id'] ?? '' );
	}

	/**
	 * Delete a post's Google Business Profile local post, and clear the stored result. A
	 * `NOT_FOUND` response means the post is already gone at Google -- treated the same as
	 * success, since the merchant-visible outcome (no Google post, no stored link) is identical
	 * either way.
	 *
	 * @param int        $post_id
	 * @param array|null $result The stored account/location/post-ID triple, if already known to
	 *                           the caller. Read from post meta when omitted.
	 *
	 * @throws BusinessProfileApiException On a non-2xx response other than Google's own NOT_FOUND, or a network failure.
	 * @throws AccountReconnect When the Jetpack connection or the Google Account needs reconnecting.
	 */
	public function delete( int $post_id, ?array $result = null ): void {
		if ( null === $result ) {
			$result = $this->get_stored_result( $post_id );
		}

		if ( ! self::has_complete_result( $result ) ) {
			return;
		}

		$account_id     = (string) $result['account_id'];
		$location_id    = (string) $result['location_id'];
		$google_post_id = (string) $result['post_id'];

		try {
			$this->client->delete( "accounts/{$account_id}/locations/{$location_id}/localPosts/{$google_post_id}" );
		} catch ( BusinessProfileApiException $exception ) {
			$google_status = $exception->get_response_body()['error']['status'] ?? '';

			if ( 404 !== $exception->get_http_status() || 'NOT_FOUND' !== $google_status ) {
				throw $exception;
			}
		}

		delete_post_meta( $post_id, self::RESULT_META_KEY );
	}

	/**
	 * Enforce the real character limit server-side, independent of whatever the FE UI
	 * already prevents.
	 *
	 * @param string $summary
	 *
	 * @return string
	 */
	private static function truncate_summary( string $summary ): string {
		return mb_substr( $summary, 0, self::SUMMARY_CHAR_LIMIT );
	}
}
