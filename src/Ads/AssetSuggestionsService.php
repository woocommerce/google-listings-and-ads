<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

/**
 * Class AssetSuggestionsService
 *
 * Suggest assets and possible final URLs.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AssetSuggestionsService implements Service {

	/**
	 * AssetSuggestionsService constructor.
	 *
	 * @param WP $wp
	 */
	public function __construct( WP $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Get posts that can be used to suggest assets
	 *
	 * @param string $search The search query.
	 * @param int    $per_page Number of items per page.
	 * @param int    $offset Used in the get_posts query.
	 *
	 * @return array formatted post suggestions
	 */
	protected function get_post_suggestions( string $search, int $per_page, int $offset = 0 ): array {
		if ( $per_page <= 0 ) {
			return [];
		}

		$post_suggestions    = [];
		$excluded_post_types = [ 'attachment' ];

		$post_types = $this->wp->get_post_types(
			[
				'exclude_from_search' => false,
				'public'              => true,
			]
		);

		// Exclude attachment post_type
		$filtered_post_types = array_diff( $post_types, $excluded_post_types );

		$args = [
			'post_type'      => $filtered_post_types,
			'posts_per_page' => $per_page,
			'post_status'    => 'publish',
			's'              => $search,
			'offset'         => $offset,
		];

		$posts = $this->wp->get_posts( $args );

		foreach ( $posts as $post ) {
			$post_suggestions[] = $this->format_final_url_response( $post->ID, 'post', $post->post_title, get_permalink( $post->ID ) );
		}

		return $post_suggestions;
	}

	/**
	 * Get terms that can be used to suggest assets
	 *
	 * @param string $search The search query
	 * @param int    $per_page Number of items per page
	 *
	 * @return array formatted terms suggestions
	 */
	protected function get_terms_suggestions( string $search, int $per_page ): array {
		$terms_suggestions = [];

		// get_terms  evaluates $per_page_terms = 0 as a falsy, therefore it will not add the LIMIT clausure returning all the results.
		// See: https://github.com/WordPress/WordPress/blob/abe134c2090e84080adc46187884201a4badd649/wp-includes/class-wp-term-query.php#L868
		if ( $per_page <= 0 ) {
			return [];
		}

		// Get all taxonomies that are public, show_in_menu = true helps to exclude taxonomies such as "product_shipping_class".
		$taxonomies = $this->wp->get_taxonomies(
			[
				'public'       => true,
				'show_in_menu' => true,
			],
		);

		$terms = $this->wp->get_terms(
			[
				'taxonomy'   => $taxonomies,
				'hide_empty' => false,
				'number'     => $per_page,
				'name__like' => $search,
			]
		);

		foreach ( $terms as $term ) {
				$terms_suggestions[] = $this->format_final_url_response( $term->term_id, 'term', $term->name, get_term_link( $term->term_id, $term->taxonomy ) );
		}

		return $terms_suggestions;

	}


	/**
	 * Return a list of final urls that can be used to suggest assets.
	 *
	 * @param string $search The search query
	 * @param int    $per_page Number of items per page
	 * @param string $order_by Order by: type, title, url
	 *
	 * @return array final urls with their title, id & type.
	 */
	public function get_final_url_suggestions( string $search = '', int $per_page = 30, string $order_by = 'title' ): array {
		if ( empty( $search ) ) {
			 return $this->get_defaults_final_url_suggestions();
		}

		// Split possible results between posts and terms.
		$per_page_posts = (int) ceil( $per_page / 2 );

		$posts = $this->get_post_suggestions( $search, $per_page_posts );

		// Try to get more results using the terms
		$per_page_terms = $per_page - count( $posts );

		$terms = $this->get_terms_suggestions( $search, $per_page_terms );

		$pending_results = $per_page - count( $posts ) - count( $terms );
		$more_results    = [];

		// Try to get more results using posts
		if ( $pending_results > 0 && count( $posts ) === $per_page_posts ) {
			$more_results = $this->get_post_suggestions( $search, $pending_results, $per_page_posts );
		}

		$result = array_merge( $posts, $terms, $more_results );

		return $this->sort_results( $result, $order_by );

	}

	/**
	 * Get defaults final urls suggestions.
	 *
	 * @return array default final urls.
	 */
	protected function get_defaults_final_url_suggestions(): array {
		// We can only offer assets if the homepage is static.
		$home_page = $this->wp->get_static_homepage();
		$shop_page = $this->wp->get_shop_page();
		$defaults  = [];

		if ( $home_page ) {
			$defaults[] = $this->format_final_url_response( $home_page->ID, 'post', 'Homepage', get_permalink( $home_page->ID ) );
		}

		if ( $shop_page ) {
			$defaults[] = $this->format_final_url_response( $shop_page->ID, 'post', $shop_page->post_title, get_permalink( $shop_page->ID ) );
		}

		return $defaults;
	}

	/**
	 *  Order suggestions alphabetically
	 *
	 *  @param array  $array associative array
	 *  @param string $field Sort by a specific field
	 *
	 * @return array response sorted alphabetically
	 */
	protected function sort_results( array $array, string $field ): array {
		usort(
			$array,
			function ( $a, $b ) use ( $field ) {
				return strcmp( strtolower( (string) $a[ $field ] ), strtolower( (string) $b[ $field ] ) );
			}
		);

		return $array;

	}

	/**
	 * Return an assotiave array with the page suggestion response format.
	 *
	 * @param int    $id post|term ID
	 * @param string $type post|term
	 * @param string $title page|term title
	 * @param string $url page|term url
	 *
	 * @return array response formated.
	 */
	protected function format_final_url_response( int $id, string $type, string $title, string $url ): array {
		return [
			'id'    => $id,
			'type'  => $type,
			'title' => $title,
			'url'   => $url,
		];

	}
}
