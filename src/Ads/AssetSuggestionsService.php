<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ArrayUtil;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ImageUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DimensionUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroupAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Exception;
use WP_Query;
use wpdb;

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
	 * WP Proxy
	 *
	 * @var WP
	 */
	protected WP $wp;

	/**
	 * WC Proxy
	 *
	 * @var WC
	 */
	protected WC $wc;

	/**
	 * Image utilities.
	 *
	 * @var ImageUtility
	 */
	protected ImageUtility $image_utility;

	/**
	 * The AdsAssetGroupAsset class.
	 *
	 * @var AdsAssetGroupAsset
	 */
	protected $asset_group_asset;

	/**
	 * WordPress database access abstraction class.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Image requirements.
	 */
	protected const IMAGE_REQUIREMENTS = [
		self::MARKETING_IMAGE_KEY        => [
			'minimum'     => [ 600, 314 ],
			'recommended' => [ 1200, 628 ],
		],
		self::SQUARE_MARKETING_IMAGE_KEY => [
			'minimum'     => [ 300, 300 ],
			'recommended' => [ 1200, 1200 ],
		],
		self::LOGO_IMAGE_KEY             => [
			'minimum'     => [ 128, 128 ],
			'recommended' => [ 1200, 1200 ],
		],

	];

	/**
	 * Default maximum marketing images.
	 */
	protected const DEFAULT_MAXIMUM_MARKETING_IMAGES = 20;

	/**
	 * The subsize key for the square marketing image.
	 */
	protected const SQUARE_MARKETING_IMAGE_KEY = 'gla_square_marketing_asset';
	/**
	 * The subsize key for the marketing image.
	 */
	protected const MARKETING_IMAGE_KEY = 'gla_marketing_asset';
	/**
	 * The subsize key for the logo image.
	 */
	protected const LOGO_IMAGE_KEY = 'gla_logo_asset';

	/**
	 * AssetSuggestionsService constructor.
	 *
	 * @param WP                 $wp WP Proxy.
	 * @param WC                 $wc WC Proxy.
	 * @param ImageUtility       $image_utility Image utility.
	 * @param wpdb               $wpdb WordPress database access abstraction class.
	 * @param AdsAssetGroupAsset $asset_group_asset The AdsAssetGroupAsset class.
	 */
	public function __construct( WP $wp, WC $wc, ImageUtility $image_utility, wpdb $wpdb, AdsAssetGroupAsset $asset_group_asset ) {
		$this->wp                = $wp;
		$this->wc                = $wc;
		$this->wpdb              = $wpdb;
		$this->image_utility     = $image_utility;
		$this->asset_group_asset = $asset_group_asset;
	}

	/**
	 * Get WP and other campaigns' assets from the specific post or term.
	 *
	 * @param int    $id Post or Term ID.
	 * @param string $type Only possible values are post or term.
	 */
	public function get_assets_suggestions( $id, string $type ): array {
		$asset_group_assets = $this->get_asset_group_asset_suggestions( $id, $type );

		if ( ! empty( $asset_group_assets ) ) {
			return $asset_group_assets;
		}

		return $this->get_wp_assets( $id, $type );
	}

	/**
	 * Get other campaigns' assets from the specific url.
	 *
	 * @param int    $id Post or Term ID.
	 * @param string $type Only possible values are post or term.
	 */
	public function get_asset_group_asset_suggestions( int $id, string $type ): array {
		if ( $type === 'post' ) {
			$final_url = get_permalink( $id );
		} else {
			$final_url = get_term_link( $id );
		}

		if ( $final_url === false || is_wp_error( $final_url ) ) {
			return [];
		}

		$assets = $this->asset_group_asset->get_assets_by_final_url( $final_url );

		if ( empty( $assets ) ) {
			return [];
		}

		if ( ! isset( $assets[ AssetFieldType::CALL_TO_ACTION_SELECTION ] ) ) {
			$assets[ AssetFieldType::CALL_TO_ACTION_SELECTION ] = null;
		}

		return array_merge( [ 'final_url' => $final_url ], $assets );

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
			$attachments_ids = [ ...$attachments_ids, ...$product->get_gallery_image_ids() ];
		}

		$attachments_ids  = [ ...$attachments_ids, ...$this->get_gallery_images_ids( $id ), get_post_thumbnail_id( $id ) ];
		$marketing_images = $this->get_url_attachments_by_ids( $attachments_ids );
		$long_headline    = get_bloginfo( 'name' ) . ': ' . $post->post_title;

		return [
			AssetFieldType::HEADLINE                 => [ $post->post_title ],
			AssetFieldType::LONG_HEADLINE            => [ $long_headline ],
			AssetFieldType::DESCRIPTION              => ArrayUtil::remove_empty_values( [ $post->post_excerpt, get_bloginfo( 'description' ) ] ),
			AssetFieldType::LOGO                     => $this->get_logo_images(),
			AssetFieldType::BUSINESS_NAME            => get_bloginfo( 'name' ),
			AssetFieldType::SQUARE_MARKETING_IMAGE   => $marketing_images[ self::SQUARE_MARKETING_IMAGE_KEY ] ?? [],
			AssetFieldType::MARKETING_IMAGE          => $marketing_images [ self::MARKETING_IMAGE_KEY ] ?? [],
			AssetFieldType::CALL_TO_ACTION_SELECTION => null,
			'display_url_path'                       => [ $post->post_name ],
			'final_url'                              => get_permalink( $id ),
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
			$attachments_ids[]            = get_post_thumbnail_id( $post->ID );
			$posts_ids_assigned_to_term[] = $post->ID;
		}

		if ( count( $posts_assigned_to_term ) ) {
			$attachments_ids = [ ...$this->get_post_image_attachments( [ 'post_parent__in' => $posts_ids_assigned_to_term ] ), ...$attachments_ids ];
		}

		$marketing_images = $this->get_url_attachments_by_ids( $attachments_ids );

		return [
			AssetFieldType::HEADLINE                 => [ $term->name ],
			AssetFieldType::LONG_HEADLINE            => [ get_bloginfo( 'name' ) . ': ' . $term->name ],
			AssetFieldType::DESCRIPTION              => ArrayUtil::remove_empty_values( [ wp_strip_all_tags( $term->description ), get_bloginfo( 'description' ) ] ),
			AssetFieldType::LOGO                     => $this->get_logo_images(),
			AssetFieldType::BUSINESS_NAME            => get_bloginfo( 'name' ),
			AssetFieldType::SQUARE_MARKETING_IMAGE   => $marketing_images[ self::SQUARE_MARKETING_IMAGE_KEY ] ?? [],
			AssetFieldType::MARKETING_IMAGE          => $marketing_images [ self::MARKETING_IMAGE_KEY ] ?? [],
			AssetFieldType::CALL_TO_ACTION_SELECTION => null,
			'display_url_path'                       => [ $term->slug ],
			'final_url'                              => get_term_link( $term->term_id ),
		];
	}

	/**
	 * Get logo images urls.
	 *
	 * @return array Logo images urls.
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
	 * Get unique attachments ids converted to int values.
	 *
	 * @param array $ids Attachments ids.
	 * @param int   $maximum_images Maximum number of images to return.
	 *
	 * @return array List of unique attachments ids converted to int values.
	 */
	protected function prepare_image_ids( array $ids, int $maximum_images = self::DEFAULT_MAXIMUM_MARKETING_IMAGES ): array {
		$ids = array_unique( ArrayUtil::remove_empty_values( $ids ) );
		$ids = array_map( 'intval', $ids );
		return array_slice( $ids, 0, $maximum_images );
	}

	/**
	 * Get URL for each attachment using an array of attachment ids and a list of subsizes.
	 *
	 * @param array $ids Attachments ids.
	 * @param array $size_keys Image subsize keys.
	 * @param int   $maximum_images Maximum number of images to return.
	 *
	 * @return array A list of attachments urls.
	 */
	protected function get_url_attachments_by_ids( array $ids, array $size_keys = [ self::SQUARE_MARKETING_IMAGE_KEY, self::MARKETING_IMAGE_KEY ], $maximum_images = self::DEFAULT_MAXIMUM_MARKETING_IMAGES ): array {
		$ids = $this->prepare_image_ids( $ids, $maximum_images );

		$marketing_images = [];

		foreach ( $ids as $id ) {

			$metadata = wp_get_attachment_metadata( $id );

			foreach ( $size_keys as $size_key ) {

				$minimum_size     = new DimensionUtility( ...self::IMAGE_REQUIREMENTS[ $size_key ]['minimum'] );
				$recommended_size = new DimensionUtility( ...self::IMAGE_REQUIREMENTS[ $size_key ]['recommended'] );
				$image_size       = new DimensionUtility( $metadata['width'], $metadata['height'] );
				$suggested_size   = $this->image_utility->recommend_size( $image_size, $recommended_size, $minimum_size );

				// If the original size matches the suggested size with a precision of +-1px.
				if ( $suggested_size && $suggested_size->equals( $image_size ) ) {
					$marketing_images[ $size_key ][] = wp_get_attachment_url( $id );
				} elseif ( isset( $metadata['sizes'][ $size_key ] ) ) {
					// use the sub size.
					$marketing_images[ $size_key ][] = wp_get_attachment_image_url( $id, $size_key );
				} elseif ( $suggested_size && $this->image_utility->maybe_add_subsize_image( $id, $size_key, $suggested_size ) ) {
					// use the resized image.
					$marketing_images[ $size_key ][] = wp_get_attachment_image_url( $id, $size_key );
				}
			}
		}

		return $marketing_images;
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
			'post_type'        => $filtered_post_types,
			'posts_per_page'   => $per_page,
			'post_status'      => 'publish',
			'search_title'     => $search,
			'offset'           => $offset,
			'suppress_filters' => false,
		];

		add_filter( 'posts_where', [ $this, 'title_filter' ], 10, 2 );

		$posts = $this->wp->get_posts( $args );

		remove_filter( 'posts_where', [ $this, 'title_filter' ] );

		foreach ( $posts as $post ) {
			$post_suggestions[] = $this->format_final_url_response( $post->ID, 'post', $post->post_title, get_permalink( $post->ID ) );
		}

		return $post_suggestions;
	}

	/**
	 * Filter for the posts_where hook, adds WHERE clause to search
	 * for the 'search_title' parameter in the post titles (when present).
	 *
	 * @param string   $where The WHERE clause of the query.
	 * @param WP_Query $wp_query The WP_Query instance (passed by reference).
	 *
	 * @return string The updated WHERE clause.
	 */
	public function title_filter( string $where, WP_Query $wp_query ): string {
		$search_title = $wp_query->get( 'search_title' );
		if ( $search_title ) {
			$title_search = '%' . $this->wpdb->esc_like( $search_title ) . '%';
			$where       .= $this->wpdb->prepare( " AND `{$this->wpdb->posts}`.`post_title` LIKE %s", $title_search ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return $where;
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
