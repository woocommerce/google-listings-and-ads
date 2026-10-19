<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Places every product shown in the Product Feed table in exactly one of five sync
 * statuses: approved, pending sync, pending review, disapproved, or not synced.
 *
 * Counts use the same units as the table: published, supported, non-variation products.
 * Variable parents carry the combined sync status of their variations, so variations
 * are not counted separately. The same SQL expression drives the counts, the table
 * filter, and each row's status, so a status tab's count always equals the rows it
 * filters to, and every row's status matches the tab it appears in.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth
 */
class ProductJourney {

	use PluginHelper;

	/** Live on Google: approved, partially approved, or approved but expiring soon. */
	public const APPROVED = 'approved';

	/** Waiting to be sent, or a temporary error that will be retried. */
	public const PENDING_SYNC = 'pending_sync';

	/** Sent to Google, review result not known yet. */
	public const PENDING_REVIEW = 'pending_review';

	/** Disapproved by Google, or the data was refused when it was sent. */
	public const DISAPPROVED = 'disapproved';

	/** Set to "Don't sync and show", or never sent. */
	public const NOT_SYNCED = 'not_synced';

	/** Every status, in display order. */
	public const SEGMENTS = [
		self::APPROVED,
		self::PENDING_SYNC,
		self::PENDING_REVIEW,
		self::DISAPPROVED,
		self::NOT_SYNCED,
	];

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * ProductJourney constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Number of products in each status.
	 *
	 * Known limitation: the Product Feed table rows also pass through the
	 * `woocommerce_gla_product_query_args` filter (ProductRepository), while these
	 * counts are plain SQL and do not. A site that narrows the product query with that
	 * filter can see tab counts above the number of rows the tab lists.
	 *
	 * @return int[] Keyed by status, in display order, including empty statuses.
	 */
	public function get_counts(): array {
		$counts = array_fill_keys( self::SEGMENTS, 0 );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance; the segment SQL is built from prepared fragments.
		$rows = $this->wpdb->get_results(
			"SELECT segment, COUNT(*) AS total FROM ( {$this->get_segment_query()} ) journey GROUP BY segment",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( (array) $rows as $row ) {
			if ( isset( $counts[ $row['segment'] ] ) ) {
				$counts[ $row['segment'] ] = (int) $row['total'];
			}
		}

		return $counts;
	}

	/**
	 * SQL selecting the IDs of the products in one status, for filtering the Product
	 * Feed table inside its own query (`ID IN ( <this SQL> )`), so the filter costs no
	 * extra round trip and the tab lists exactly the products its count covers.
	 *
	 * @param string $segment One of self::SEGMENTS.
	 *
	 * @return string|null Prepared SQL, or null for an unknown status.
	 */
	public function get_product_ids_sql( string $segment ): ?string {
		if ( ! in_array( $segment, self::SEGMENTS, true ) ) {
			return null;
		}

		return "SELECT id FROM ( {$this->get_segment_query()} ) journey WHERE segment = " . $this->wpdb->prepare( '%s', $segment );
	}

	/**
	 * The status of each of the given products, for the Product Feed table rows.
	 *
	 * One query for the whole list. Products the table would not list (drafts,
	 * variations, unsupported types) are left out of the result.
	 *
	 * @param int[] $product_ids
	 *
	 * @return string[] Status keyed by product ID.
	 */
	public function get_product_segments( array $product_ids ): array {
		$product_ids = array_values( array_unique( array_filter( array_map( 'intval', $product_ids ) ) ) );
		if ( empty( $product_ids ) ) {
			return [];
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance; the segment SQL is built from prepared fragments.
		$rows = $this->wpdb->get_results(
			"SELECT id, segment FROM ( {$this->get_segment_query( $product_ids )} ) journey",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$segments = [];
		foreach ( (array) $rows as $row ) {
			if ( in_array( $row['segment'], self::SEGMENTS, true ) ) {
				$segments[ (int) $row['id'] ] = $row['segment'];
			}
		}

		return $segments;
	}

	/**
	 * One row per product: `id`, `segment`.
	 *
	 * Order of precedence: hidden from the store catalog or set to "Don't sync and show"
	 * (not synced, whatever the product's earlier sync results), then local queue state
	 * (waiting or retrying: pending sync; refused on send: disapproved), then Google's
	 * review result for products that were sent, and finally "not synced" for
	 * everything else. Expiring products are still live, so they count as approved.
	 *
	 * @param int[] $product_ids Limit the rows to these products. Empty for all products.
	 *
	 * @return string Prepared SQL.
	 */
	protected function get_segment_query( array $product_ids = [] ): string {
		$types          = $this->get_product_types();
		$id_limit       = $product_ids ? ' AND p.ID IN ( ' . implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) ) . ' )' : '';
		$catalog_hidden = $this->get_catalog_hidden_sql( 'p.ID' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline; the placeholder lists are generated locally.
		return $this->wpdb->prepare(
			"SELECT p.ID AS id,
				CASE
					WHEN {$catalog_hidden} THEN %s
					WHEN vis.meta_value = %s THEN %s
					WHEN st.meta_value = %s THEN %s
					WHEN st.meta_value = %s AND err.meta_value LIKE %s THEN %s
					WHEN st.meta_value = %s THEN %s
					WHEN st.meta_value = %s AND mc.meta_value IN ( %s, %s, %s ) THEN %s
					WHEN st.meta_value = %s AND mc.meta_value = %s THEN %s
					WHEN st.meta_value = %s THEN %s
					ELSE %s
				END AS segment
			FROM {$this->wpdb->posts} p
			INNER JOIN {$this->wpdb->term_relationships} tr ON tr.object_id = p.ID
			INNER JOIN {$this->wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_type'
			INNER JOIN {$this->wpdb->terms} t ON t.term_id = tt.term_id
			LEFT JOIN {$this->wpdb->postmeta} vis ON vis.post_id = p.ID AND vis.meta_key = %s
			LEFT JOIN {$this->wpdb->postmeta} st ON st.post_id = p.ID AND st.meta_key = %s
			LEFT JOIN {$this->wpdb->postmeta} mc ON mc.post_id = p.ID AND mc.meta_key = %s
			LEFT JOIN {$this->wpdb->postmeta} err ON err.post_id = p.ID AND err.meta_key = %s
			WHERE p.post_type = 'product'
				AND p.post_status = 'publish'
				AND t.slug IN ( " . implode( ', ', array_fill( 0, count( $types ), '%s' ) ) . " ){$id_limit}",
			array_merge(
				[
					self::NOT_SYNCED,
					ChannelVisibility::DONT_SYNC_AND_SHOW,
					self::NOT_SYNCED,
					SyncStatus::PENDING,
					self::PENDING_SYNC,
					SyncStatus::HAS_ERRORS,
					'%' . $this->wpdb->esc_like( GoogleProductService::INTERNAL_ERROR_REASON ) . '%',
					self::PENDING_SYNC,
					SyncStatus::HAS_ERRORS,
					self::DISAPPROVED,
					SyncStatus::SYNCED,
					MCStatus::APPROVED,
					MCStatus::PARTIALLY_APPROVED,
					MCStatus::EXPIRING,
					self::APPROVED,
					SyncStatus::SYNCED,
					MCStatus::DISAPPROVED,
					self::DISAPPROVED,
					SyncStatus::SYNCED,
					self::PENDING_REVIEW,
					self::NOT_SYNCED,
					$this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY ),
					$this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ),
					$this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ),
					$this->prefix_meta_key( ProductMetaHandler::KEY_ERRORS ),
				],
				$types,
				$product_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * SQL condition: the product is hidden from the store catalog, so it is not synced
	 * and its channel visibility cannot be set. Mirrors WC_Product::is_visible() as the
	 * product page and ProductHelper::is_sync_ready() use it, outside a search:
	 * - catalog visibility "Shop and search results" or "Shop only" is visible; "Search
	 *   only" and "Hidden" carry the `exclude-from-catalog` term and are not;
	 * - with "Hide out of stock items from the catalog" on, an out-of-stock product
	 *   (`outofstock` term) is not.
	 *
	 * It cannot mirror the `woocommerce_product_is_visible` filter, or products whose
	 * visibility terms are out of sync with their settings.
	 *
	 * @param string $id_column The column holding the product ID, e.g. `p.ID`.
	 *
	 * @return string SQL condition, safe to embed (no placeholders, no user input).
	 */
	public function get_catalog_hidden_sql( string $id_column ): string {
		$terms = [ 'exclude-from-catalog' ];
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$terms[] = 'outofstock';
		}

		return "EXISTS (
				SELECT 1 FROM {$this->wpdb->term_relationships} hidden_tr
				INNER JOIN {$this->wpdb->term_taxonomy} hidden_tt ON hidden_tt.term_taxonomy_id = hidden_tr.term_taxonomy_id AND hidden_tt.taxonomy = 'product_visibility'
				INNER JOIN {$this->wpdb->terms} hidden_t ON hidden_t.term_id = hidden_tt.term_id
				WHERE hidden_tr.object_id = {$id_column} AND hidden_t.slug IN ( '" . implode( "', '", $terms ) . "' )
			)";
	}

	/**
	 * SQL condition: the product's effective channel visibility is the given one. A
	 * product hidden from the store catalog counts as "Don't sync and show" whatever
	 * its setting; one without a setting counts as "Sync and show".
	 *
	 * @param string $visibility One of ChannelVisibility::ALLOWED_VALUES.
	 * @param string $id_column  The column holding the product ID, e.g. `p.ID`.
	 *
	 * @return string Prepared SQL condition.
	 */
	public function get_effective_visibility_sql( string $visibility, string $id_column ): string {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The column name is passed by this plugin's code only.
		$dont_sync = $this->wpdb->prepare(
			"EXISTS ( SELECT 1 FROM %i vis WHERE vis.post_id = {$id_column} AND vis.meta_key = %s AND vis.meta_value = %s )",
			$this->wpdb->postmeta,
			$this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY ),
			ChannelVisibility::DONT_SYNC_AND_SHOW
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$hidden = $this->get_catalog_hidden_sql( $id_column );

		return ChannelVisibility::DONT_SYNC_AND_SHOW === $visibility
			? "( {$dont_sync} OR {$hidden} )"
			: "( NOT {$dont_sync} AND NOT {$hidden} )";
	}

	/**
	 * Product types the Product Feed table lists: supported types without variations.
	 *
	 * @return string[]
	 */
	public function get_product_types(): array {
		$types = array_filter(
			array_diff( ProductSyncer::get_supported_product_types(), [ 'variation' ] ),
			'is_string'
		);

		return $types ? array_values( $types ) : [ 'simple', 'variable' ];
	}
}
