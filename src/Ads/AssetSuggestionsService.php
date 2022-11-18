<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

/**
 * Class AssetSuggestionsService
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
	 * @return array
	 */
	protected function get_post_suggestions(): array {
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
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];

		$posts = $this->wp->get_posts( $args );

		foreach ( $posts as $post ) {
			$post_suggestions[] = $this->format_page_response( $post->ID, 'post', $post->post_type, $post->post_title, get_permalink( $post->ID ) );
		}

		return $post_suggestions;
	}

	/**
	 * Get terms that can be used to suggest assets
	 *
	 * @return array
	 */
	protected function get_terms_suggestion(): array {
		$terms_suggestions = [];

		// Get all taxonomies that are public, show_in_menu = true helps to exclude taxonomies such as "product_shipping_class"
		$taxonomies = $this->wp->get_taxonomies(
			[
				'public'       => true,
				'show_in_menu' => false,
			],
		);

		$terms = $this->wp->get_terms(
			[
				'taxonomy'   => $taxonomies,
				'hide_empty' => false,
			]
		);

		foreach ( $terms as $term ) {
				$terms_suggestions[] = $this->format_page_response( $term->term_id, 'term', null, $term->name, get_term_link( $term->term_id, $term->taxonomy ) );
		}

		return $terms_suggestions;

	}


	/**
	 * Return a list of pages that can be used to suggest assets.
	 *
	 * @return array Array of pages
	 */
	public function get_pages_suggestions(): array {
		$posts = $this->get_post_suggestions();
		$terms = $this->get_terms_suggestion();
		return array_merge( $posts, $terms );
	}

	/**
	 * Return an assotiave array with the page suggestion response format.
	 *
	 * @param int    $id post|term ID
	 * @param string $type post|term
	 * @param string $post_type Post type if exists
	 * @param string $title page|term title
	 * @param string $url page|term url
	 *
	 * @return array
	 */
	protected function format_page_response( $id, $type, $post_type, $title, $url ): array {
		return [
			'id'        => $id,
			'type'      => $type,
			'post_type' => $post_type,
			'title'     => $title,
			'url'       => $url,
		];

	}
}
