<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductImageSizeAudit
 *
 * Counts the products synced to Google Merchant Center whose main image is smaller than
 * the recommended minimum dimensions.
 *
 * Variable parent products are excluded: ProductHelper::mark_as_synced() stores Google IDs on
 * them, but only their variations are sent to Merchant Center.
 *
 * The main image is resolved the same way as WCProductAdapter::map_wc_product_image():
 * the product image, then the parent product image for variations, then the first gallery
 * image (the variation's own gallery, falling back to the parent's). Dimensions are read from
 * the attachment metadata of the "full" size, which is the size sent to Google.
 *
 * The audit reads post meta directly instead of loading WC_Product objects so it stays cheap
 * on large catalogs. Images replaced through product filters, or offloaded images without
 * local attachment metadata, are not counted.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductImageSizeAudit {

	use PluginHelper;

	/**
	 * Minimum width and height, in pixels, a main product image should have.
	 */
	public const MIN_IMAGE_DIMENSION = 500;

	/**
	 * Number of synced products to inspect per query.
	 */
	protected const BATCH_SIZE = 1000;

	/** @var wpdb */
	protected $wpdb;

	/** @var TransientsInterface */
	protected $transients;

	/**
	 * ProductImageSizeAudit constructor.
	 *
	 * @param wpdb                $wpdb
	 * @param TransientsInterface $transients
	 */
	public function __construct( wpdb $wpdb, TransientsInterface $transients ) {
		$this->wpdb       = $wpdb;
		$this->transients = $transients;
	}

	/**
	 * Get the number of synced products whose main image is smaller than MIN_IMAGE_DIMENSION
	 * in width or height. The result is cached for a day.
	 *
	 * @param bool $force_refresh Recalculate the count even if a cached value exists.
	 *
	 * @return int
	 */
	public function get_small_image_product_count( bool $force_refresh = false ): int {
		$count = $force_refresh ? null : $this->transients->get( TransientsInterface::SMALL_IMAGE_PRODUCT_COUNT );

		if ( null === $count ) {
			$count = $this->count_small_image_products();
			$this->transients->set( TransientsInterface::SMALL_IMAGE_PRODUCT_COUNT, $count, DAY_IN_SECONDS );
		}

		return (int) $count;
	}

	/**
	 * Count synced products with a small main image, paging through them by ID.
	 *
	 * @return int
	 */
	protected function count_small_image_products(): int {
		$count            = 0;
		$last_id          = 0;
		$variable_term_id = $this->get_variable_product_term_taxonomy_id();

		do {
			$rows      = $this->get_synced_product_image_rows( $last_id, $variable_term_id );
			$row_count = count( $rows );
			if ( 0 === $row_count ) {
				break;
			}

			$last_id = (int) end( $rows )->ID;

			// Key by product ID so a product with duplicate meta rows is only counted once.
			$image_ids = [];
			foreach ( $rows as $row ) {
				$image_ids[ (int) $row->ID ] = $this->resolve_image_id( $row );
			}
			$image_ids = array_filter( $image_ids );
			if ( empty( $image_ids ) ) {
				continue;
			}

			$dimensions = $this->get_image_dimensions( array_unique( $image_ids ) );
			foreach ( $image_ids as $image_id ) {
				if ( isset( $dimensions[ $image_id ] ) && $this->is_too_small( $dimensions[ $image_id ] ) ) {
					++$count;
				}
			}
		} while ( self::BATCH_SIZE === $row_count );

		return $count;
	}

	/**
	 * Get the term taxonomy ID of the "variable" product type, used to exclude variable parents.
	 *
	 * @return int The term taxonomy ID, or 0 if the term does not exist.
	 */
	protected function get_variable_product_term_taxonomy_id(): int {
		$term = get_term_by( 'slug', 'variable', 'product_type' );

		return $term ? (int) $term->term_taxonomy_id : 0;
	}

	/**
	 * Get the image meta of a batch of synced products (including variations, excluding variable
	 * parents) after the given ID.
	 *
	 * @param int $last_id
	 * @param int $variable_term_id Term taxonomy ID of the "variable" product type.
	 *
	 * @return object[] Rows with ID, thumbnail_id, parent_thumbnail_id, gallery and parent_gallery.
	 */
	protected function get_synced_product_image_rows( int $last_id, int $variable_term_id ): array {
		$posts              = $this->wpdb->posts;
		$postmeta           = $this->wpdb->postmeta;
		$term_relationships = $this->wpdb->term_relationships;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table names from $wpdb.
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT p.ID,
					thumb.meta_value AS thumbnail_id,
					parent_thumb.meta_value AS parent_thumbnail_id,
					gallery.meta_value AS gallery,
					parent_gallery.meta_value AS parent_gallery
				FROM {$posts} p
				INNER JOIN {$postmeta} synced ON synced.post_id = p.ID AND synced.meta_key = %s
				LEFT JOIN {$postmeta} thumb ON thumb.post_id = p.ID AND thumb.meta_key = '_thumbnail_id'
				LEFT JOIN {$postmeta} gallery ON gallery.post_id = p.ID AND gallery.meta_key = '_product_image_gallery'
				LEFT JOIN {$postmeta} parent_thumb ON p.post_parent > 0 AND parent_thumb.post_id = p.post_parent AND parent_thumb.meta_key = '_thumbnail_id'
				LEFT JOIN {$postmeta} parent_gallery ON p.post_parent > 0 AND parent_gallery.post_id = p.post_parent AND parent_gallery.meta_key = '_product_image_gallery'
				WHERE p.post_type IN ( 'product', 'product_variation' )
				AND p.ID > %d
				AND NOT EXISTS (
					SELECT 1 FROM {$term_relationships} tr
					WHERE tr.object_id = p.ID AND tr.term_taxonomy_id = %d
				)
				ORDER BY p.ID ASC
				LIMIT %d",
				$this->prefix_meta_key( ProductMetaHandler::KEY_GOOGLE_IDS ),
				$last_id,
				$variable_term_id,
				self::BATCH_SIZE
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Resolve the main image ID for a product row, mirroring WCProductAdapter::map_wc_product_image().
	 *
	 * @param object $row
	 *
	 * @return int The attachment ID, or 0 if the product has no image.
	 */
	protected function resolve_image_id( $row ): int {
		$image_id = absint( $row->thumbnail_id ?? 0 ) ?: absint( $row->parent_thumbnail_id ?? 0 );
		if ( $image_id ) {
			return $image_id;
		}

		$gallery = (string) ( $row->gallery ?? '' ) ?: (string) ( $row->parent_gallery ?? '' );

		return absint( strtok( $gallery, ',' ) );
	}

	/**
	 * Get the "full" size dimensions for a set of attachments.
	 *
	 * @param int[] $image_ids
	 *
	 * @return array Map of attachment ID to [ width, height ]. Attachments without dimensions are omitted.
	 */
	protected function get_image_dimensions( array $image_ids ): array {
		$postmeta     = $this->wpdb->postmeta;
		$placeholders = implode( ',', array_fill( 0, count( $image_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table names from $wpdb and built %d placeholders.
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT post_id, meta_value FROM {$postmeta}
				WHERE meta_key = '_wp_attachment_metadata' AND post_id IN ( {$placeholders} )",
				...array_values( $image_ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$dimensions = [];
		foreach ( (array) $rows as $row ) {
			$metadata = maybe_unserialize( $row->meta_value );
			if ( is_array( $metadata ) && ! empty( $metadata['width'] ) && ! empty( $metadata['height'] ) ) {
				$dimensions[ (int) $row->post_id ] = [ (int) $metadata['width'], (int) $metadata['height'] ];
			}
		}

		return $dimensions;
	}

	/**
	 * Whether either side of an image is below the minimum dimension.
	 *
	 * @param int[] $dimensions [ width, height ]
	 *
	 * @return bool
	 */
	protected function is_too_small( array $dimensions ): bool {
		return $dimensions[0] < self::MIN_IMAGE_DIMENSION || $dimensions[1] < self::MIN_IMAGE_DIMENSION;
	}
}
