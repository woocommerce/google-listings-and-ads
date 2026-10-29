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

	/** @var string[] Statuses of a post a URL can resolve to. Attachment pages, which use `inherit`, are allowed separately. */
	protected const RESOLVABLE_POST_STATUSES = [ 'publish', 'private' ];

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

		$url         = $this->to_home_url( $url );
		$query_slugs = $this->get_query_slugs( $url );

		// reason: WordPress answers a query that names a post by slug with that post or a 404, whatever the path.
		// url_to_postid() drops the query string, so it would map `/?product=blue-shirt` to a static front page.
		if ( ! empty( $query_slugs ) ) {
			$post_id = $this->find_post_id_by_query_slug( $query_slugs );

			return 0 !== $post_id ? $post_id : $this->find_post_id_by_old_query_slug( $query_slugs, $old_slug_post_types );
		}

		$post_id = url_to_postid( $url );

		// reason: url_to_postid() reads an ID query such as `?page_id=` without checking the post's status or type, and
		// maps every URL at the home path to a static front page, including archive queries such as `?product_cat=`.
		if ( 0 !== $post_id && ( ! $this->is_resolvable_post( $post_id ) || $this->is_front_page_archive_query( $url, $post_id ) ) ) {
			$post_id = 0;
		}

		if ( 0 === $post_id ) {
			$post_id = $this->find_posts_page_id( $url );
		}

		if ( 0 === $post_id ) {
			$post_id = $this->find_page_id_by_permalink( wc_get_page_id( 'shop' ), $url );
		}

		// WordPress only follows a former slug when nothing else answers the URL, so a post type archive keeps its URL.
		if ( 0 === $post_id && ! $this->is_post_type_archive_url( $url ) ) {
			$post_id = $this->find_post_id_by_old_slug( $url, $old_slug_post_types );
		}

		return $post_id;
	}

	/**
	 * Whether a URL can resolve to the post: it's published or private, or it's an attachment page.
	 *
	 * Revisions share the attachments' `inherit` status, so the post type is checked too.
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	protected function is_resolvable_post( int $post_id ): bool {
		$post_type = (string) get_post_type( $post_id );

		// A variation isn't viewable on its own, but the product report resolves its URL to the parent product.
		if ( ! is_post_type_viewable( $post_type ) && 'product_variation' !== $post_type ) {
			return false;
		}

		$status = get_post_status( $post_id );

		if ( in_array( $status, self::RESOLVABLE_POST_STATUSES, true ) ) {
			return true;
		}

		return 'inherit' === $status && 'attachment' === $post_type;
	}

	/**
	 * Whether the URL is an archive query, such as `/?product_cat=shoes` or `/?s=shoes`, that `url_to_postid()`
	 * mapped to the static front page.
	 *
	 * @param string $url
	 * @param int    $post_id Post ID from `url_to_postid()`.
	 *
	 * @return bool
	 */
	protected function is_front_page_archive_query( string $url, int $post_id ): bool {
		if ( 'page' !== get_option( 'show_on_front' ) || (int) get_option( 'page_on_front' ) !== $post_id ) {
			return false;
		}

		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_vars );

		$archive_query_vars = [ 's', 'post_type', 'cat', 'tag', 'author', 'author_name', 'm', 'year', 'monthnum', 'day', 'feed' ];

		foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $taxonomy ) {
			if ( is_string( $taxonomy->query_var ) && '' !== $taxonomy->query_var ) {
				$archive_query_vars[] = $taxonomy->query_var;
			}
		}

		return ! empty( array_intersect_key( $query_vars, array_flip( $archive_query_vars ) ) );
	}

	/**
	 * Whether the URL's path is the archive of a public post type, such as the WooCommerce shop page.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	protected function is_post_type_archive_url( string $url ): bool {
		$path = self::get_comparable_path( $url );

		foreach ( get_post_types( [ 'public' => true ] ) as $post_type ) {
			$archive_link = get_post_type_archive_link( $post_type );

			if ( is_string( $archive_link ) && self::get_comparable_path( $archive_link ) === $path ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the URL is the store's own: the same host, ignoring a leading "www.", under the store's path.
	 *
	 * Compared against the plugin's canonical site URL, the same one Search Console properties are matched on.
	 * The path matters for a store installed in a subdirectory, since a domain property covers the whole host.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public function is_store_url( string $url ): bool {
		return $this->is_under_url( $url, $this->get_site_url() );
	}

	/**
	 * Whether the URL's path is the store's home path, ignoring its query string.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public function is_home_path( string $url ): bool {
		return $this->is_store_url( $url ) && self::get_comparable_path( $this->to_home_url( $url ) ) === self::get_comparable_path( home_url() );
	}

	/**
	 * Whether a URL is on the same host as a base URL, ignoring a leading "www.", and under its path.
	 *
	 * @param string $url
	 * @param string $base_url
	 *
	 * @return bool
	 */
	protected function is_under_url( string $url, string $base_url ): bool {
		$url_host  = wp_parse_url( $url, PHP_URL_HOST );
		$base_host = wp_parse_url( $base_url, PHP_URL_HOST );

		if ( ! is_string( $url_host ) || ! is_string( $base_host ) ) {
			return false;
		}

		if ( preg_replace( '/^www\./i', '', strtolower( $url_host ) ) !== preg_replace( '/^www\./i', '', strtolower( $base_host ) ) ) {
			return false;
		}

		$url_path  = trailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$base_path = trailingslashit( (string) wp_parse_url( $base_url, PHP_URL_PATH ) );

		return 0 === strpos( $url_path, $base_path );
	}

	/**
	 * Rewrite a store URL onto `home_url()`, which `url_to_postid()` and permalinks are built on.
	 *
	 * The store URL comes from the plugin's filterable site URL, so it can differ from `home_url()` in its
	 * scheme, host or path, and `url_to_postid()` matches nothing on a URL outside `home_url()`. A URL that's
	 * already under `home_url()` is kept as it is.
	 *
	 * @param string $url A URL that {@see self::is_store_url()} accepted.
	 *
	 * @return string
	 */
	protected function to_home_url( string $url ): string {
		if ( $this->is_under_url( $url, home_url() ) ) {
			return $url;
		}

		$path       = (string) wp_parse_url( $url, PHP_URL_PATH );
		$query      = (string) wp_parse_url( $url, PHP_URL_QUERY );
		$store_path = untrailingslashit( (string) wp_parse_url( $this->get_site_url(), PHP_URL_PATH ) );

		return untrailingslashit( home_url() ) . '/' . ltrim( (string) substr( $path, strlen( $store_path ) ), '/' ) . ( '' === $query ? '' : '?' . $query );
	}

	/**
	 * Find the page that shows the latest posts, which `url_to_postid()` doesn't map since it isn't a singular view.
	 *
	 * @param string $url
	 *
	 * @return int The page ID, or 0 when the URL isn't the posts page.
	 */
	protected function find_posts_page_id( string $url ): int {
		if ( 'page' !== get_option( 'show_on_front' ) ) {
			return 0;
		}

		return $this->find_page_id_by_permalink( (int) get_option( 'page_for_posts' ), $url );
	}

	/**
	 * Get a page's ID when it can resolve to a post and its permalink is at the URL's path.
	 *
	 * Used for pages that `url_to_postid()` doesn't map, such as the posts page and the WooCommerce Shop page,
	 * whose URL the product archive answers.
	 *
	 * @param int    $page_id The page to check, or a value below 1 when the store has none.
	 * @param string $url
	 *
	 * @return int The page ID, or 0 when the URL isn't the page's.
	 */
	protected function find_page_id_by_permalink( int $page_id, string $url ): int {
		if ( $page_id <= 0 || ! $this->is_resolvable_post( $page_id ) ) {
			return 0;
		}

		$permalink = get_permalink( $page_id );

		return is_string( $permalink ) && self::get_comparable_path( $permalink ) === self::get_comparable_path( $url ) ? $page_id : 0;
	}

	/**
	 * Get the post slugs a plain-permalink query names, such as `?product=blue-shirt`.
	 *
	 * `url_to_postid()` drops the query string, so it only sees ID queries such as `?p=` and `?page_id=`.
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

		// WordPress reads these built-in query variables itself, since the core post types don't register their own.
		$query_var_post_types = [
			'name'       => 'post',
			'pagename'   => 'page',
			'attachment' => 'attachment',
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
	 * Find the post a plain-permalink query names by its current slug, when a URL can resolve to it.
	 *
	 * @param array[] $query_slugs From {@see self::get_query_slugs()}.
	 *
	 * @return int The post ID, or 0 when the query names no post.
	 */
	protected function find_post_id_by_query_slug( array $query_slugs ): int {
		foreach ( $query_slugs as $query_slug ) {
			$post = get_page_by_path( $query_slug['slug'], OBJECT, $query_slug['post_type'] );

			if ( $post instanceof WP_Post && $this->is_resolvable_post( $post->ID ) ) {
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
	 * Only a post whose permalink sits under the same parent path matches, so a term archive that
	 * happens to share a former slug, such as `/product-category/sale/`, isn't credited to it. A term
	 * archive under the same parent path as the post, such as a category base set to the root, still can be.
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
				'post_status'      => self::RESOLVABLE_POST_STATUSES,
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
	 * Get a URL's path, decoded and without a trailing slash, so two URLs of the same page compare equal.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected static function get_comparable_path( string $url ): string {
		return untrailingslashit( rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
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
