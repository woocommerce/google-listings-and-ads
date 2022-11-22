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
	 * @param string $search The search query
	 * @param int    $per_page Number of items per page
	 *
	 * @return array
	 */
	protected function get_post_suggestions( $search, $per_page ): array {
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
	 * @return array
	 */
	protected function get_terms_suggestion( $search, $per_page ): array {
		$terms_suggestions = [];

		// Get all taxonomies that are public, show_in_menu = true helps to exclude taxonomies such as "product_shipping_class".
		$taxonomies = $this->wp->get_taxonomies(
			[
				'public'       => true,
				'show_in_menu' => true,
			],
		);

		// Skip empty terms
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
	 * @param string $order_by Order by: id, type, title, url
	 *
	 * @return array Array of final urls with their title, id & type.
	 */
	public function get_final_urls_suggestions( $search = '', $per_page = 30, $order_by = 'title' ): array {
		if ( $per_page <= 0 ) {
			return [];
		}

		// Split possible results between posts and terms.
		$per_page_posts = (int) ceil( $per_page / 2 );
		$per_page_terms = (int) $per_page - $per_page_posts;

		$posts = $this->get_post_suggestions( $search, $per_page_posts );

		// If not posts results, try to get all results from terms.
		if ( count( $posts ) === 0 ) {
			$result = $this->get_terms_suggestion( $search, $per_page );
			return $this->order_results( $result, $order_by );
		}

		// Try to get more results using the terms
		$terms  = $this->get_terms_suggestion( $search, $per_page_terms );
		$result = array_merge( $posts, $terms );

		return $this->order_results( $result, $order_by );

	}

	/**
	 *  Order suggestions alphabetically
	 *
	 *  @param array  $array associative array
	 *  @param string $field Sort by a specific field
	 */
	public function order_results( $array, $field ) {
		usort(
			$array,
			function ( $a, $b ) use ( $field ) {
				return strcmp( (string) $a[ $field ], (string) $b[ $field ] );
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
	 * @return array
	 */
	protected function format_final_url_response( $id, $type, $title, $url ): array {
		return [
			'id'    => $id,
			'type'  => $type,
			'title' => $title,
			'url'   => $url,
		];

	}
}
