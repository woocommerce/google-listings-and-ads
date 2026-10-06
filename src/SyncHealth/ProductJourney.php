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
 * Places every product shown in the Product Feed table in exactly one journey segment,
 * from "not syncing" to "live on Google".
 *
 * Counts use the same units as the table: published, supported, non-variation products.
 * Variable parents carry the combined sync status of their variations, so variations
 * are not counted separately. The same SQL expression drives both the counts and the
 * table filter, so a segment's count always equals the rows it filters to.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth
 */
class ProductJourney {

	use PluginHelper;

	public const LIVE             = 'live';
	public const EXPIRING         = 'expiring';
	public const IN_REVIEW        = 'in_review';
	public const WAITING          = 'waiting';
	public const RETRYING         = 'retrying';
	public const DISAPPROVED      = 'disapproved';
	public const REJECTED_ON_SEND = 'rejected';
	public const HIDDEN           = 'hidden';
	public const NOT_SENT         = 'not_sent';

	/** Every segment, in display order. */
	public const SEGMENTS = [
		self::LIVE,
		self::EXPIRING,
		self::IN_REVIEW,
		self::WAITING,
		self::RETRYING,
		self::DISAPPROVED,
		self::REJECTED_ON_SEND,
		self::HIDDEN,
		self::NOT_SENT,
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
	 * Number of products in each segment.
	 *
	 * @return int[] Keyed by segment, in display order, including empty segments.
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
	 * IDs of the products in one segment, for filtering the Product Feed table.
	 *
	 * @param string $segment One of self::SEGMENTS.
	 *
	 * @return int[]
	 */
	public function get_product_ids( string $segment ): array {
		if ( ! in_array( $segment, self::SEGMENTS, true ) ) {
			return [];
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance; the segment SQL is built from prepared fragments.
		$ids = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT id FROM ( {$this->get_segment_query()} ) journey WHERE segment = %s",
				$segment
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * One row per product: `id`, `segment`.
	 *
	 * Order of precedence: hidden by the merchant, then local queue state (waiting,
	 * retrying, rejected), then Google's review result for products that were sent,
	 * and finally "not sent" for everything else.
	 *
	 * @return string Prepared SQL.
	 */
	protected function get_segment_query(): string {
		$types = $this->get_product_types();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		return $this->wpdb->prepare(
			"SELECT p.ID AS id,
				CASE
					WHEN vis.meta_value = %s THEN %s
					WHEN st.meta_value = %s THEN %s
					WHEN st.meta_value = %s AND err.meta_value LIKE %s THEN %s
					WHEN st.meta_value = %s THEN %s
					WHEN st.meta_value = %s AND mc.meta_value IN ( %s, %s ) THEN %s
					WHEN st.meta_value = %s AND mc.meta_value = %s THEN %s
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
				AND t.slug IN ( " . implode( ', ', array_fill( 0, count( $types ), '%s' ) ) . ' )',
			array_merge(
				[
					ChannelVisibility::DONT_SYNC_AND_SHOW,
					self::HIDDEN,
					SyncStatus::PENDING,
					self::WAITING,
					SyncStatus::HAS_ERRORS,
					'%' . $this->wpdb->esc_like( GoogleProductService::INTERNAL_ERROR_REASON ) . '%',
					self::RETRYING,
					SyncStatus::HAS_ERRORS,
					self::REJECTED_ON_SEND,
					SyncStatus::SYNCED,
					MCStatus::APPROVED,
					MCStatus::PARTIALLY_APPROVED,
					self::LIVE,
					SyncStatus::SYNCED,
					MCStatus::EXPIRING,
					self::EXPIRING,
					SyncStatus::SYNCED,
					MCStatus::DISAPPROVED,
					self::DISAPPROVED,
					SyncStatus::SYNCED,
					self::IN_REVIEW,
					self::NOT_SENT,
					$this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY ),
					$this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_STATUS ),
					$this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ),
					$this->prefix_meta_key( ProductMetaHandler::KEY_ERRORS ),
				],
				$types
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Product types the Product Feed table lists: supported types without variations.
	 *
	 * @return string[]
	 */
	protected function get_product_types(): array {
		$types = array_filter(
			array_diff( ProductSyncer::get_supported_product_types(), [ 'variation' ] ),
			'is_string'
		);

		return $types ? array_values( $types ) : [ 'simple', 'variable' ];
	}
}
