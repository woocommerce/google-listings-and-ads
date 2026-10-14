<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ISOUtility;
use Exception;
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

	/** @var string REST field exposing whether a post has been shared, and its searchUrl once Google provides one. */
	public const SHARE_STATUS_FIELD_NAME = 'gla_gbp_share_status';

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

	/**
	 * LocalPostService constructor.
	 *
	 * @param BusinessProfileApiClient $client
	 * @param WP                       $wp
	 * @param ISOUtility               $iso_utility
	 */
	public function __construct( BusinessProfileApiClient $client, WP $wp, ISOUtility $iso_utility ) {
		$this->client      = $client;
		$this->wp          = $wp;
		$this->iso_utility = $iso_utility;
	}

	/**
	 * Register this feature's post-meta schema and REST field.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_fields' ] );
	}

	/**
	 * Register the Summary and result post-meta keys.
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
		$result         = get_post_meta( $post_id, self::RESULT_META_KEY, true );
		$result         = is_array( $result ) ? $result : [];
		$google_post_id = (string) ( $result['post_id'] ?? '' );

		return [
			'is_shared'  => '' !== $google_post_id,
			'search_url' => (string) ( $result['search_url'] ?? '' ),
		];
	}

	/**
	 * Build and send a `localPosts.create` request for a WordPress post, and store the
	 * result keyed to that post.
	 *
	 * @param WP_Post $post
	 *
	 * @return LocalPost The created post, as returned by Google.
	 * @throws Exception When no Business Profile location is connected, or Google's response carries no post identifier.
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

		$local_post = new LocalPost(
			self::truncate_summary( (string) get_post_meta( $post->ID, self::SUMMARY_META_KEY, true ) ),
			$this->iso_utility->wp_locale_to_bcp47( $this->wp->get_locale() ),
			(string) get_permalink( $post ),
			(string) ( get_the_post_thumbnail_url( $post, 'full' ) ?: '' )
		);

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
			throw new Exception(
				__( 'Google Business Profile returned no post identifier.', 'google-listings-and-ads' ),
				502
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

		return $created;
	}

	/**
	 * Whether a stored result carries all three IDs a delete call needs. Shared with
	 * {@see PostDeleteTrigger::handle_pre_delete()} so the two checks can't drift apart.
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
			$result = get_post_meta( $post_id, self::RESULT_META_KEY, true );
			$result = is_array( $result ) ? $result : [];
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
