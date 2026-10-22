<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class PagePostResolver
 *
 * Resolves a Search Console page URL to the post it belongs to.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class PagePostResolver {

	use PluginHelper;

	/** @var string Post meta key WordPress uses to remember a post's previous slugs. */
	protected const OLD_SLUG_META_KEY = '_wp_old_slug';

	/**
	 * Resolve a page URL to a post ID.
	 *
	 * A URL whose slug has since changed still resolves to the post that used to have it.
	 *
	 * @param string   $url                 Absolute page URL as reported by Search Console.
	 * @param string[] $old_slug_post_types Post types searched for a post that used to have the URL's slug.
	 *
	 * @return int The post ID, or 0 when the URL isn't a post on this site.
	 */
	public function resolve_post_id( string $url, array $old_slug_post_types ): int {
		if ( ! $this->is_store_url( $url ) ) {
			return 0;
		}

		$post_id = url_to_postid( $url );

		if ( 0 === $post_id ) {
			$post_id = $this->find_post_id_by_old_slug( $url, $old_slug_post_types );
		}

		return $post_id;
	}

	/**
	 * Whether the URL's host is the store's own, ignoring a leading "www.".
	 *
	 * Compared against the plugin's canonical site URL, the same one Search Console properties are matched on.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public function is_store_url( string $url ): bool {
		$url_host   = wp_parse_url( $url, PHP_URL_HOST );
		$store_host = wp_parse_url( $this->get_site_url(), PHP_URL_HOST );

		if ( ! is_string( $url_host ) || ! is_string( $store_host ) ) {
			return false;
		}

		return preg_replace( '/^www\./i', '', strtolower( $url_host ) ) === preg_replace( '/^www\./i', '', strtolower( $store_host ) );
	}

	/**
	 * Find the published or private post that previously used the slug at the end of the URL.
	 *
	 * Private posts are matched so both lookups agree: `url_to_postid()` matches them for a
	 * user who can read them, and their clicks still count in the report.
	 *
	 * @param string   $url
	 * @param string[] $post_types
	 *
	 * @return int The post ID, or 0 when no post used that slug.
	 */
	protected function find_post_id_by_old_slug( string $url, array $post_types ): int {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$slug = sanitize_title( rawurldecode( basename( untrailingslashit( $path ) ) ) );

		if ( '' === $slug || empty( $post_types ) ) {
			return 0;
		}

		// reason: `_wp_old_slug` is only reachable through postmeta, and there is no cheaper lookup for a former slug.
		// Filters are suppressed so a multilingual plugin can't narrow the query to the current language.
		// If several posts once used the same slug, the most recently published one wins, so the result is stable.
		$post_ids = get_posts(
			[
				'post_type'        => array_values( $post_types ),
				'post_status'      => [ 'publish', 'private' ],
				'meta_key'         => self::OLD_SLUG_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'no_found_rows'    => true,
				'suppress_filters' => true,
			]
		);

		return (int) ( $post_ids[0] ?? 0 );
	}
}
