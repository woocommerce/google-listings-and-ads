<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WP_Post;

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

	/** @var int Most posts checked for a former slug, since only those under the URL's parent path match. */
	protected const MAX_OLD_SLUG_CANDIDATES = 10;

	/**
	 * Resolve a page URL to a post ID.
	 *
	 * A URL whose slug has since changed still resolves to the post that used to have it, and a
	 * plain-permalink URL such as `/?product=blue-shirt` resolves to the post its query names,
	 * by its current or a former slug.
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

		$post_id     = url_to_postid( $url );
		$query_slugs = 0 === $post_id ? $this->get_query_slugs( $url ) : [];

		if ( 0 === $post_id ) {
			$post_id = $this->find_post_id_by_query_slug( $query_slugs );
		}

		if ( 0 === $post_id ) {
			$post_id = $this->find_post_id_by_old_slug( $url, $old_slug_post_types );
		}

		if ( 0 === $post_id ) {
			$post_id = $this->find_post_id_by_old_query_slug( $query_slugs, $old_slug_post_types );
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
	 * Get the post slugs a plain-permalink query names, such as `?product=blue-shirt`.
	 *
	 * `url_to_postid()` only reads ID queries (`?p=`, `?page_id=`) when the site has no rewrite rules.
	 *
	 * @param string $url
	 *
	 * @return array[] Each `{ post_type: string, slug: string }`, in the order WordPress reads the query.
	 */
	protected function get_query_slugs( string $url ): array {
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );

		if ( '' === $query ) {
			return [];
		}

		wp_parse_str( $query, $query_vars );

		$query_var_post_types = [
			'name'     => 'post',
			'pagename' => 'page',
		];

		foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) {
			if ( is_string( $post_type->query_var ) && '' !== $post_type->query_var ) {
				$query_var_post_types[ $post_type->query_var ] = $post_type->name;
			}
		}

		$query_slugs = [];

		foreach ( $query_var_post_types as $query_var => $post_type ) {
			if ( ! empty( $query_vars[ $query_var ] ) && is_string( $query_vars[ $query_var ] ) ) {
				$query_slugs[] = [
					'post_type' => $post_type,
					'slug'      => $query_vars[ $query_var ],
				];
			}
		}

		return $query_slugs;
	}

	/**
	 * Find the published or private post a plain-permalink query names by its current slug.
	 *
	 * @param array[] $query_slugs From {@see self::get_query_slugs()}.
	 *
	 * @return int The post ID, or 0 when the query names no post.
	 */
	protected function find_post_id_by_query_slug( array $query_slugs ): int {
		foreach ( $query_slugs as $query_slug ) {
			$post = get_page_by_path( $query_slug['slug'], OBJECT, $query_slug['post_type'] );

			if ( $post instanceof WP_Post && in_array( $post->post_status, [ 'publish', 'private' ], true ) ) {
				return $post->ID;
			}
		}

		return 0;
	}

	/**
	 * Find the published or private post that previously used the slug a plain-permalink query names,
	 * such as `?product=old-slug`, which WordPress redirects to the renamed post.
	 *
	 * The query names the post type, so only that type is searched, and only when it's one of the given types.
	 *
	 * @param array[]  $query_slugs From {@see self::get_query_slugs()}.
	 * @param string[] $post_types
	 *
	 * @return int The post ID, or 0 when no post used that slug.
	 */
	protected function find_post_id_by_old_query_slug( array $query_slugs, array $post_types ): int {
		foreach ( $query_slugs as $query_slug ) {
			if ( ! in_array( $query_slug['post_type'], $post_types, true ) ) {
				continue;
			}

			$slug = sanitize_title( basename( $query_slug['slug'] ) );

			if ( '' === $slug ) {
				continue;
			}

			$candidate_ids = $this->get_old_slug_candidates( $slug, [ $query_slug['post_type'] ], 1 );

			if ( ! empty( $candidate_ids ) ) {
				return (int) $candidate_ids[0];
			}
		}

		return 0;
	}

	/**
	 * Find the published or private post that previously used the slug at the end of the URL.
	 *
	 * Private posts are matched so both lookups agree: `url_to_postid()` matches them for a
	 * user who can read them, and their clicks still count in the report.
	 *
	 * Only a post whose permalink sits under the same parent path matches, so an archive that
	 * happens to share a former slug, such as `/product-category/sale/`, isn't credited to it.
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

		$candidate_ids = $this->get_old_slug_candidates( $slug, $post_types, self::MAX_OLD_SLUG_CANDIDATES );
		$parent_path   = self::get_parent_path( $path );

		foreach ( $candidate_ids as $candidate_id ) {
			$permalink = get_permalink( $candidate_id );

			if ( is_string( $permalink ) && $parent_path === self::get_parent_path( (string) wp_parse_url( $permalink, PHP_URL_PATH ) ) ) {
				return (int) $candidate_id;
			}
		}

		return 0;
	}

	/**
	 * Get the published or private posts that previously used a slug, most recently published first.
	 *
	 * @param string   $slug
	 * @param string[] $post_types
	 * @param int      $limit
	 *
	 * @return int[]
	 */
	protected function get_old_slug_candidates( string $slug, array $post_types, int $limit ): array {
		// reason: `_wp_old_slug` is only reachable through postmeta, and there is no cheaper lookup for a former slug.
		// Filters are suppressed so a multilingual plugin can't narrow the query to the current language.
		// If several posts once used the same slug, the most recently published one wins, so the result is stable.
		return get_posts(
			[
				'post_type'        => array_values( $post_types ),
				'post_status'      => [ 'publish', 'private' ],
				'meta_key'         => self::OLD_SLUG_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'posts_per_page'   => $limit,
				'no_found_rows'    => true,
				'suppress_filters' => true,
			]
		);
	}

	/**
	 * Get a URL path without its last segment, decoded so encoded and plain slugs compare equal.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected static function get_parent_path( string $path ): string {
		return rawurldecode( dirname( '/' . trim( $path, '/' ) ) );
	}
}
