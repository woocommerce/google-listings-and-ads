<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ArrayUtil;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Exception;

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
	 * Default maximum marketing images.
	 */
	protected const DEFAULT_MAXIMUM_MARKETING_IMAGES = 20;
	/**
	 * The subsize key for the square marketing image.
	 */
	protected const SQUARE_MARKETING_IMAGE_KEY = 'gla_square_marketing';
	/**
	 * The subsize key for the marketing image.
	 */
	protected const MARKETING_IMAGE_KEY = 'gla_marketing';
	/**
	 * The subsize key for the marketing image.
	 */
	protected const LOGO_IMAGE_KEY = 'gla_logo';
	/**
	 * The minimum size for a square marketing image. The first value is the width and the second is the height.
	 */
	protected const MARKETING_SQUARE_IMAGE_MINIMUM_SIZE = [ 300, 300 ];
	/**
	 * The minimum size for a marketing image. The first value is the width and the second is the height.
	 */
	protected const MARKETING_IMAGE_MINIMUM_SIZE = [ 600, 314 ];
	/**
	 * The minimum size for the logo image. The first value is the width and the second is the height.
	 */
	protected const LOGO_IMAGE_MINIMUM_SIZE = [ 128, 128 ];

	/**
	 * Minimum image requirements.
	 *
	 * @var array
	 */
	protected $minimum_image_requirements;

	/**
	 * AssetSuggestionsService constructor.
	 *
	 * @param WP $wp WP Proxy.
	 * @param WC $wc WC Proxy.
	 */
	public function __construct( WP $wp, WC $wc ) {
		$this->wp = $wp;
		$this->wc = $wc;

		$this->minimum_image_requirements[ self::MARKETING_IMAGE_KEY ]        = self::MARKETING_IMAGE_MINIMUM_SIZE;
		$this->minimum_image_requirements[ self::SQUARE_MARKETING_IMAGE_KEY ] = self::MARKETING_SQUARE_IMAGE_MINIMUM_SIZE;
		$this->minimum_image_requirements[ self::LOGO_IMAGE_KEY ]             = self::LOGO_IMAGE_MINIMUM_SIZE;

	}

	/**
	 * Get WP and other campaigns' assets from the specific post or term.
	 *
	 * @param int    $id Post or Term ID.
	 * @param string $type Only possible values are post or term.
	 */
	public function get_assets_suggestions( int $id, string $type ): array {
		// TODO: Fetch assets from others campaigns and merge the result with the WP Assets.
		return $this->get_wp_assets( $id, $type );
	}

	/**
	 * Get assets from specific post or term.
	 *
	 * @param int    $id Post or Term ID.
	 * @param string $type Only possible values are post or term.
	 *
	 * @return array All assets available for specific term or post.
	 */
	protected function get_wp_assets( int $id, string $type ): array {
		if ( $type === 'post' ) {
			return $this->get_post_assets( $id );
		}

		return $this->get_term_assets( $id );

	}

	/**
	 * Get assets from specific post.
	 *
	 * @param int $id Post ID.
	 *
	 * @return array All assets for specific post.
	 * @throws Exception If the Post ID is invalid.
	 */
	protected function get_post_assets( int $id ): array {
		$post = get_post( $id );

		if ( ! $post || $post->post_status === 'trash' ) {
			throw new Exception(
				/* translators: 1: is an integer representing an unknown Post ID */
				sprintf( __( 'Invalid Post ID %1$d', 'google-listings-and-ads' ), $id )
			);
		}

		$attachments_ids = $this->get_post_image_attachments(
			[
				'post_parent' => $id,
			]
		);

		if ( $id === wc_get_page_id( 'shop' ) ) {
			$attachments_ids = [ ...$attachments_ids, ...$this->get_shop_attachments() ];
		}

		if ( $post->post_type === 'product' || $post->post_type === 'product_variation' ) {
			$product         = $this->wc->maybe_get_product( $id );
			$attachments_ids = [ ...$attachments_ids, ...$product->get_gallery_image_ids(), $product->get_image_id() ];
		}

		$attachments_ids  = array_slice( [ ...$attachments_ids, ...$this->get_gallery_images_ids( $id ) ], 0, self::DEFAULT_MAXIMUM_MARKETING_IMAGES );
		$marketing_images = $this->get_url_attachments_by_ids( $attachments_ids );
		$long_headline    = get_bloginfo( 'name' ) . ': ' . $post->post_title;

		return [
			'headline'                => [ $post->post_title ],
			'long_headline'           => [ $long_headline ],
			'description'             => ArrayUtil::remove_empty_values( [ $post->post_excerpt, get_bloginfo( 'description' ) ] ),
			'logo'                    => $this->get_logo_images(),
			'final_url'               => get_permalink( $id ),
			'business_name'           => get_bloginfo( 'name' ),
			'display_url_path'        => [ $post->post_name ],
			'square_marketing_images' => $marketing_images[ self::SQUARE_MARKETING_IMAGE_KEY ] ?? [],
			'marketing_images'        => $marketing_images [ self::MARKETING_IMAGE_KEY ] ?? [],
			'call_to_action'          => null,
		];
	}

	/**
	 * Get assets from specific term.
	 *
	 * @param int $id Term ID.
	 *
	 * @return array All assets for specific term.
	 * @throws Exception If the Term ID is invalid.
	 */
	protected function get_term_assets( int $id ): array {
		$term = get_term( $id );

		if ( ! $term ) {
			throw new Exception(
				/* translators: 1: is an integer representing an unknown Term ID */
				sprintf( __( 'Invalid Term ID %1$d', 'google-listings-and-ads' ), $id )
			);
		}

		$posts_assigned_to_term     = $this->get_posts_assigned_to_a_term( $term->term_id, $term->taxonomy );
		$posts_ids_assigned_to_term = [];
		$attachments_ids            = [];

		foreach ( $posts_assigned_to_term as $post ) {

			if ( $post->post_type === 'product' ) {
				$product           = $this->wc->maybe_get_product( $post->ID );
				$attachments_ids[] = $product->get_image_id();
			}

			$posts_ids_assigned_to_term[] = $post->ID;
		}

		if ( count( $posts_assigned_to_term ) ) {
			$attachments_ids = [ ...$this->get_post_image_attachments( [ 'post_parent__in' => $posts_ids_assigned_to_term ] ), ...$attachments_ids ];
		}

		$attachments_ids  = array_slice( $attachments_ids, 0, self::DEFAULT_MAXIMUM_MARKETING_IMAGES );
		$marketing_images = $this->get_url_attachments_by_ids( $attachments_ids );

		return [
			'headline'                => [ $term->name ],
			'long_headline'           => [ get_bloginfo( 'name' ) . ': ' . $term->name ],
			'description'             => ArrayUtil::remove_empty_values( [ wp_strip_all_tags( $term->description ), get_bloginfo( 'description' ) ] ),
			'logo'                    => $this->get_logo_images(),
			'final_url'               => get_term_link( $term->term_id ),
			'business_name'           => get_bloginfo( 'name' ),
			'display_url_path'        => [ $term->slug ],
			'square_marketing_images' => $marketing_images[ self::SQUARE_MARKETING_IMAGE_KEY ] ?? [],
			'marketing_images'        => $marketing_images [ self::MARKETING_IMAGE_KEY ] ?? [],
			'call_to_action'          => null,
		];
	}

	/**
	 * Get logo images urls.
	 *
	 * @return array Logo images URLS.
	 */
	protected function get_logo_images(): array {
		$logo_images = $this->get_url_attachments_by_ids( [ get_theme_mod( 'custom_logo' ) ], [ self::LOGO_IMAGE_KEY ] );
		return $logo_images[ self::LOGO_IMAGE_KEY ] ?? [];
	}

	/**
	 * Get posts linked to a specific term.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $taxonomy_name Taxonomy name.
	 *
	 * @return array List of posts assigned to the term.
	 */
	protected function get_posts_assigned_to_a_term( int $term_id, string $taxonomy_name ): array {
		$args = [
			'post_type'   => 'any',
			'numberposts' => self::DEFAULT_MAXIMUM_MARKETING_IMAGES,
			'tax_query'   => [
				[
					'taxonomy'         => $taxonomy_name,
					'terms'            => $term_id,
					'field'            => 'term_id',
					'include_children' => false,
				],
			],
		];

		return $this->wp->get_posts( $args );
	}

	/**
	 * Get attachments related to the shop page.
	 *
	 * @return array Shop attachments.
	 */
	protected function get_shop_attachments(): array {
		return $this->get_post_image_attachments(
			[
				'post_parent__in' => $this->get_shop_products(),
			]
		);
	}

	/**
	 *
	 * Get products that will be use to offer image assets.
	 *
	 * @param array $args See WP_Query::parse_query() for all available arguments.
	 * @return array Shop products.
	 */
	protected function get_shop_products( array $args = [] ): array {
		$defaults = [
			'post_type'   => 'product',
			'numberposts' => self::DEFAULT_MAXIMUM_MARKETING_IMAGES,
			'fields'      => 'ids',
		];

		$args = wp_parse_args( $args, $defaults );

		return $this->wp->get_posts( $args );
	}

	/**
	 * Get gallery images ids.
	 *
	 * @param int $post_id Post ID that contains the gallery.
	 *
	 * @return array List of gallery images ids.
	 */
	protected function get_gallery_images_ids( int $post_id ): array {
		$gallery = get_post_gallery( $post_id, false );

		if ( ! $gallery || ! isset( $gallery['ids'] ) ) {
			return [];
		}

		return explode( ',', $gallery['ids'] );
	}

	/**
	 * Get URL for each attachment using an array of attachment ids.
	 *
	 * @param array $ids Attachments ids.
	 * @param array $size_keys Subsize keys that we would like to create.
	 *
	 * @return array A list of attachments urls.
	 */
	protected function get_url_attachments_by_ids( array $ids, array $size_keys = [ self::SQUARE_MARKETING_IMAGE_KEY, self::MARKETING_IMAGE_KEY ] ): array {
		$ids = array_unique( ArrayUtil::remove_empty_values( $ids ) );
		$ids = array_map( 'intval', $ids );

		$marketing_images = [];

		foreach ( $ids as $id ) {
			$metadata = wp_get_attachment_metadata( $id );
			foreach ( $size_keys as $size_key ) {
				if ( isset( $metadata['sizes'][ $size_key ] ) ) {
					$marketing_images[ $size_key ][] = wp_get_attachment_image_url( $id, $size_key );
				} elseif ( $this->check_image_size( [ $metadata['width'], $metadata['height'] ], $this->minimum_image_requirements[ $size_key ] ) && self::try_add_subsize_image( $id, $size_key, $this->minimum_image_requirements[ $size_key ][0], $this->minimum_image_requirements[ $size_key ][1] ) ) {
					$marketing_images[ $size_key ][] = wp_get_attachment_image_url( $id, $size_key );
				} else {
					continue;
				}
			}
		}

		return $marketing_images;
	}

	/**
	 * Try to add a new subsize image.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $subsize_key The subsize key that we are trying to generate.
	 * @param int    $width Image width in pixels.
	 * @param int    $height Image height in pixels.
	 * @param bool   $crop Whether to crop the image.
	 *
	 * @return bool True if the subsize has been added to the attachment metadata otherwise false.
	 */
	public static function try_add_subsize_image( int $attachment_id, string $subsize_key, int $width, int $height, bool $crop = true ): bool {
		// It is required as wp_update_image_subsizes is not loaded automatically.
		if ( ! function_exists( 'wp_update_image_subsizes' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		add_image_size( $subsize_key, $width, $height, $crop );

		$metadata = wp_update_image_subsizes( $attachment_id );

		remove_image_size( $subsize_key );

		if ( is_wp_error( $metadata ) ) {
			return false;
		}

		return isset( $metadata['sizes'][ $subsize_key ] );
	}

	/**
	 * Checks image size
	 *
	 * @param array $size Width and height values in pixels (in that order).
	 * @param array $minimum Minimum width and height values in pixels (in that order).
	 *
	 * @return bool true if the image size fulfils the minimum requirements otherwise false.
	 */
	protected function check_image_size( array $size, array $minimum ): bool {
		return $size[0] >= $minimum[0] && $size[1] >= $minimum[1];
	}



	/**
	 * Get Attachmets for specific posts.
	 *
	 * @param array $args See WP_Query::parse_query() for all available arguments.
	 *
	 * @return array List of attachments
	 */
	protected function get_post_image_attachments( array $args = [] ): array {
		$defaults = [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'fields'         => 'ids',
			'numberposts'    => self::DEFAULT_MAXIMUM_MARKETING_IMAGES,
		];

		$args = wp_parse_args( $args, $defaults );

		return $this->wp->get_posts( $args );
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
