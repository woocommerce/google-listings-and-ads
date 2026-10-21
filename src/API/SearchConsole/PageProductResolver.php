<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class PageProductResolver
 *
 * Resolves a Search Console page URL to the WooCommerce product it belongs to.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class PageProductResolver {

	use PluginHelper;

	/** @var string Post meta key WordPress uses to remember a post's previous slugs. */
	protected const OLD_SLUG_META_KEY = '_wp_old_slug';

	/** @var ProductHelper */
	protected $product_helper;

	/**
	 * PageProductResolver constructor.
	 *
	 * @param ProductHelper $product_helper
	 */
	public function __construct( ProductHelper $product_helper ) {
		$this->product_helper = $product_helper;
	}

	/**
	 * Resolve a page URL to a product ID.
	 *
	 * A URL that belongs to a variation resolves to the parent product. A URL whose
	 * slug has since changed still resolves to the product that used to have it.
	 *
	 * @param string $url Absolute page URL as reported by Search Console.
	 *
	 * @return int|null The product ID, or null when the URL isn't a product on this site.
	 */
	public function resolve_product_id( string $url ): ?int {
		if ( ! $this->is_store_url( $url ) ) {
			return null;
		}

		$post_id = url_to_postid( $url );

		if ( 0 === $post_id ) {
			$post_id = $this->find_post_id_by_old_slug( $url );
		}

		if ( 0 === $post_id ) {
			return null;
		}

		try {
			return $this->product_helper->maybe_swap_for_parent_id( $post_id );
		} catch ( InvalidValue $e ) {
			return null;
		}
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
	protected function is_store_url( string $url ): bool {
		$url_host   = wp_parse_url( $url, PHP_URL_HOST );
		$store_host = wp_parse_url( $this->get_site_url(), PHP_URL_HOST );

		if ( ! is_string( $url_host ) || ! is_string( $store_host ) ) {
			return false;
		}

		return preg_replace( '/^www\./i', '', strtolower( $url_host ) ) === preg_replace( '/^www\./i', '', strtolower( $store_host ) );
	}

	/**
	 * Find the published or private product that previously used the slug at the end of the URL.
	 *
	 * Private products are matched so both lookups agree: `url_to_postid()` matches them for a
	 * user who can read them, and their clicks still count in the report.
	 *
	 * @param string $url
	 *
	 * @return int The product ID, or 0 when no product used that slug.
	 */
	protected function find_post_id_by_old_slug( string $url ): int {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$slug = sanitize_title( rawurldecode( basename( untrailingslashit( $path ) ) ) );

		if ( '' === $slug ) {
			return 0;
		}

		// reason: `_wp_old_slug` is only reachable through postmeta, and there is no cheaper lookup for a former slug.
		// Filters are suppressed so a multilingual plugin can't narrow the query to the current language.
		// If several products once used the same slug, the most recently published one wins, so the result is stable.
		$post_ids = get_posts(
			[
				'post_type'        => 'product',
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
