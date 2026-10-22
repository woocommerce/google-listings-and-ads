<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use DateTimeInterface;
use WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopStorePagesService
 *
 * Builds the list of top organic landing pages that aren't products, ranked by clicks, with
 * each Search Console page URL resolved to its page name.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class TopStorePagesService extends AbstractTopPagesService {

	/** @var int Default number of pages returned. */
	public const DEFAULT_LIMIT = 100;

	/** @var string[] Post types left out of the report, since their pages belong to the top product pages report. */
	protected const PRODUCT_POST_TYPES = [ 'product', 'product_variation' ];

	/** @var PagePostResolver */
	protected $resolver;

	/**
	 * TopStorePagesService constructor.
	 *
	 * @param SearchAnalyticsService $search_analytics
	 * @param PagePostResolver       $resolver
	 * @param TransientsInterface    $transients
	 */
	public function __construct( SearchAnalyticsService $search_analytics, PagePostResolver $resolver, TransientsInterface $transients ) {
		parent::__construct( $search_analytics, $transients );

		$this->resolver = $resolver;
	}

	/**
	 * Get the top store pages of a property by clicks for a date range.
	 *
	 * @param string            $property Search Console property identifier (`siteUrl`).
	 * @param DateTimeInterface $after    First day of the period.
	 * @param DateTimeInterface $before   Last day of the period.
	 * @param int|null          $limit    Maximum number of pages, kept between 1 and the API's maximum. Defaults to {@see self::get_top_store_pages_limit()}.
	 *
	 * @return array[] Up to the limit of rows, each with `post_id`, `name`, `url`, `clicks`,
	 *                 `impressions`, `ctr` and `position`, sorted by clicks descending. `post_id`
	 *                 is null for a page that isn't a post, such as an archive.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	public function get_top_store_pages( string $property, DateTimeInterface $after, DateTimeInterface $before, ?int $limit = null ): array {
		$limit = null === $limit ? $this->get_top_store_pages_limit() : SearchAnalyticsService::clamp_limit( $limit );

		return $this->get_top_pages( $property, $after, $before, $limit );
	}

	/**
	 * Get the number of top store pages to return.
	 *
	 * The default is a hard limit that protects performance; a developer can raise it with the
	 * `woocommerce_gla_search_console_top_store_pages_limit` filter.
	 *
	 * @return int Between 1 and the API's maximum row limit, or the default when the filtered value isn't a number.
	 */
	public function get_top_store_pages_limit(): int {
		/**
		 * Filters the maximum number of top store pages returned from Search Console.
		 *
		 * @param int $limit Maximum number of pages. Defaults to 100.
		 */
		$limit = apply_filters( 'woocommerce_gla_search_console_top_store_pages_limit', self::DEFAULT_LIMIT );

		return SearchAnalyticsService::validate_filtered_limit( $limit, self::DEFAULT_LIMIT );
	}

	/**
	 * Get the transient the report's results are cached in.
	 *
	 * @return string
	 */
	protected function get_cache_name(): string {
		return TransientsInterface::TOP_STORE_PAGES;
	}

	/**
	 * Group the URLs of a post under its ID, and keep any other store URL on its own.
	 *
	 * Products are searched for a former slug too, so an old product URL is left out
	 * rather than kept as a page of its own.
	 *
	 * @param string $url
	 *
	 * @return string|null The group key, or null for a product or a URL on another host.
	 */
	protected function get_page_key( string $url ): ?string {
		// Checked here as well as in the resolver, which returns 0 for both another host and an unresolved store URL.
		if ( ! $this->resolver->is_store_url( $url ) ) {
			return null;
		}

		$post_id = $this->resolver->resolve_post_id( $url, get_post_types( [ 'public' => true ] ) );

		// The host is already known to be the store's, so `http://`, `https://` and `www.` copies of a URL share one row,
		// and so do copies with or without a trailing slash or a tracking parameter.
		if ( 0 === $post_id ) {
			return 'url:' . untrailingslashit( rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) . self::get_page_query( $url );
		}

		if ( in_array( get_post_type( $post_id ), self::PRODUCT_POST_TYPES, true ) ) {
			return null;
		}

		return 'post:' . $post_id;
	}

	/**
	 * Identify a page by its post ID, name and current URL.
	 *
	 * A post is named by its title. Any other page, or a post without a title, is named by its URL path
	 * and query string, so a plain-permalink archive such as `/?product_cat=shoes` isn't just "/".
	 *
	 * @param string $key Group key from {@see self::get_page_key()}.
	 * @param string $url
	 *
	 * @return array
	 */
	protected function describe_page( string $key, string $url ): array {
		$post_id = 0 === strpos( $key, 'post:' ) ? (int) substr( $key, 5 ) : null;
		$name    = '';

		if ( null !== $post_id ) {
			// reason: get_the_title() adds a "Private:" or "Protected:" prefix, and the stored title may hold HTML entities.
			// The report shows plain text.
			$name = html_entity_decode( wp_strip_all_tags( get_post_field( 'post_title', $post_id ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$url  = get_permalink( $post_id ) ?: $url;
		}

		if ( '' === $name ) {
			$name = self::get_path_name( $url );
		}

		return [
			'post_id' => $post_id,
			'name'    => $name,
			'url'     => $url,
		];
	}

	/**
	 * Get a URL's decoded path and the query that selects its page, such as `/shop/?orderby=price`.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected static function get_path_name( string $url ): string {
		return rawurldecode( (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?: '/' ) ) . self::get_page_query( $url );
	}

	/**
	 * Get the part of a URL's query that WordPress reads, sorted, such as `?orderby=price&product_cat=shoes`.
	 *
	 * Parameters WordPress ignores, such as `srsltid` or `utm_source`, don't change the page, so they're left out.
	 *
	 * @param string $url
	 *
	 * @return string The query with a leading `?`, or an empty string when nothing in it selects the page.
	 */
	protected static function get_page_query( string $url ): string {
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_vars );

		// reason: post types and taxonomies add their query variables to the global WP object, which isn't set up in
		// every context, so their query variables are added here directly as well.
		$wp                = $GLOBALS['wp'] ?? null;
		$public_query_vars = array_filter( (array) apply_filters( 'query_vars', $wp instanceof WP ? $wp->public_query_vars : ( new WP() )->public_query_vars ), 'is_string' );

		foreach ( array_merge( get_post_types( [ 'public' => true ], 'objects' ), get_taxonomies( [ 'public' => true ], 'objects' ) ) as $object ) {
			if ( is_string( $object->query_var ) && '' !== $object->query_var ) {
				$public_query_vars[] = $object->query_var;
			}
		}

		$query_vars = array_intersect_key( $query_vars, array_flip( $public_query_vars ) );

		if ( empty( $query_vars ) ) {
			return '';
		}

		ksort( $query_vars );

		return '?' . rawurldecode( http_build_query( $query_vars ) );
	}
}
