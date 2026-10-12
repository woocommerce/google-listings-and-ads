<?php
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
 * Builds and sends the `localPosts.create` request for a WordPress post and stores the
 * result as post meta. The Summary text is read from post meta and never written here;
 * callers decide when to invoke {@see self::create()}.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class LocalPostService implements Service, OptionsAwareInterface, Registerable {

	use OptionsAwareTrait;

	/** @var string Post meta key holding the merchant-edited Summary text. FE-written, read-only here. */
	public const SUMMARY_META_KEY = '_wc_gla_gbp_summary';

	/** @var string Post meta key holding the account/location/post-ID triple and searchUrl from a successful create. */
	public const RESULT_META_KEY = '_wc_gla_gbp_post';

	/** @var int The real `summary` character limit, confirmed by Google. */
	private const SUMMARY_CHAR_LIMIT = 1500;

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
	 * Register this feature's post-meta schema.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_meta' ] );
	}

	/**
	 * Register the two post-meta keys this feature depends on. The Summary key is REST-visible
	 * so Gutenberg's own already-synced data store can read and write it, with no new REST
	 * endpoint. The result key is not: it's a server-written record a later lifecycle action
	 * trusts, and has no REST reader of its own yet.
	 */
	public function register_post_meta(): void {
		register_post_meta(
			'post',
			self::SUMMARY_META_KEY,
			[
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);

		register_post_meta(
			'post',
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

	/**
	 * Build and send a `localPosts.create` request for a WordPress post, and store the
	 * result keyed to that post.
	 *
	 * @param WP_Post $post
	 *
	 * @return LocalPost The created post, as returned by Google.
	 * @throws Exception When no Business Profile location is connected.
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

		$created = LocalPost::from_array( $response );

		update_post_meta(
			$post->ID,
			self::RESULT_META_KEY,
			[
				'account_id'  => $account_id,
				'location_id' => $location_id,
				'post_id'     => BusinessProfileApiClient::get_resource_id( $created->get_name() ?? '' ),
				'search_url'  => $created->get_search_url() ?? '',
			]
		);

		return $created;
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
