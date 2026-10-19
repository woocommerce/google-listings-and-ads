<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantIssueQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\ProductJourney;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WP_Query;
use WP_REST_Request;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFeedQueryHelper
 *
 * ContainerAware used to access:
 * - MerchantCenterService
 * - MerchantIssueQuery
 * - MerchantStatuses
 * - ProductHelper
 * - ProductJourney
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
class ProductFeedQueryHelper implements ContainerAwareInterface, Service {

	use ContainerAwareTrait;
	use PluginHelper;

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * @var WP_REST_Request
	 */
	protected $request;

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * Meta key for total sales.
	 */
	protected const META_KEY_TOTAL_SALES = 'total_sales';

	/**
	 * ProductFeedQueryHelper constructor.
	 *
	 * @param wpdb              $wpdb
	 * @param ProductRepository $product_repository
	 */
	public function __construct( wpdb $wpdb, ProductRepository $product_repository ) {
		$this->wpdb               = $wpdb;
		$this->product_repository = $product_repository;
	}

	/**
	 * Retrieve an array of product information using the request params.
	 *
	 * Each product also carries its `catalog_visible` flag, its `sync_status_group` (one of
	 * ProductJourney::SEGMENTS) and its `issues`, the last two loaded with one query each
	 * for the whole page.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 *
	 * @throws InvalidValue If the orderby or visibility value isn't valid.
	 * @throws Exception If the status data can't be retrieved from Google.
	 */
	public function get( WP_REST_Request $request ): array {
		$this->request          = $request;
		$products               = [];
		$args                   = $this->prepare_query_args();
		list( $limit, $offset ) = $this->prepare_query_pagination();

		// Starts a status refresh when the cached results have expired. A refresh keeps
		// the previous statuses until it replaces them, so rows show the last known
		// status meanwhile.
		$mc_service = $this->container->get( MerchantCenterService::class );
		if ( $mc_service->is_connected() ) {
			$this->container->get( MerchantStatuses::class )->maybe_refresh_status_data();
		}

		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		add_filter( 'posts_where', [ $this, 'filter_posts_where' ], 10, 2 );
		try {
			$found = $this->product_repository->find( $args, $limit, $offset );
		} finally {
			remove_filter( 'posts_where', [ $this, 'filter_posts_where' ] );
		}

		foreach ( $found as $product ) {
			$id        = $product->get_id();
			$errors    = $product_helper->get_validation_errors( $product );
			$mc_status = $product_helper->get_mc_status( $product ) ?: $product_helper->get_sync_status( $product );

			$products[ $id ] = [
				'id'              => $id,
				'title'           => $product->get_name(),
				'visible'         => $product_helper->get_channel_visibility( $product ) !== ChannelVisibility::DONT_SYNC_AND_SHOW,
				'status'          => $mc_status,
				'image_url'       => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
				'price'           => $product->get_price(),
				'errors'          => array_values( $errors ),
				// Same check as the product page's channel visibility setting
				// (Admin::get_channel_visibility_data()): a product hidden from the
				// store catalog is not synced, whatever its channel visibility.
				'catalog_visible' => (bool) $product->is_visible(),
			];
		}

		if ( empty( $products ) ) {
			return [];
		}

		/** @var ProductJourney $journey */
		$journey   = $this->container->get( ProductJourney::class );
		$segments  = $journey->get_product_segments( array_keys( $products ) );
		$mc_issues = $this->get_merchant_center_issues( array_keys( $products ) );

		foreach ( $products as $id => $product ) {
			$products[ $id ]['sync_status_group'] = $segments[ $id ] ?? ProductJourney::NOT_SYNCED;
			$products[ $id ]['issues']            = array_merge(
				$mc_issues[ $id ] ?? [],
				$this->get_sync_error_issues( $product['errors'] )
			);
		}

		return array_values( $products );
	}

	/**
	 * Merchant Center product issues for a page of products.
	 *
	 * Issues are stored against the Merchant Center entry's WooCommerce ID, which is
	 * the variation for variable products, so variation issues are listed on their
	 * parent. An issue shared by several variations is listed once. Two queries per
	 * page: the page's variation IDs, then the issues of all those IDs.
	 *
	 * @param int[] $product_ids Product IDs on the page.
	 *
	 * @return array[] Issues keyed by product ID, errors first.
	 */
	protected function get_merchant_center_issues( array $product_ids ): array {
		$product_ids = array_map( 'intval', $product_ids );
		$owners      = array_combine( $product_ids, $product_ids );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $this->wpdb is the injected wpdb instance, the query is prepared inline, and the IN-list placeholders are generated locally from the ID count.
		$variations = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT ID, post_parent FROM {$this->wpdb->posts}
				WHERE post_type = 'product_variation'
					AND post_parent IN ( " . implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) ) . ' )',
				$product_ids
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		foreach ( (array) $variations as $variation ) {
			$owners[ (int) $variation['ID'] ] = (int) $variation['post_parent'];
		}

		/** @var MerchantIssueQuery $query */
		$query = $this->container->get( MerchantIssueQuery::class );
		$query->where( 'product_id', array_keys( $owners ), 'IN' )
			->where( 'type', MerchantStatuses::TYPE_PRODUCT )
			// Pre-sync rows copy the local errors, which are read live from product meta instead.
			->where( 'source', [ MerchantStatuses::ISSUE_SOURCE_PRESYNC ], 'NOT IN' )
			->set_order( 'issue' )
			->set_order( 'id' );

		$issues = [];
		foreach ( (array) $query->get_results() as $row ) {
			$owner = $owners[ (int) $row['product_id'] ] ?? null;
			if ( null === $owner ) {
				continue;
			}

			$severity = MerchantStatuses::standardize_issue_severity( (string) $row['severity'] );
			$key      = $row['code'] . '|' . $row['issue'];

			if ( isset( $issues[ $owner ][ $key ] ) ) {
				// The same issue on several variations: keep the most severe.
				if ( MerchantStatuses::SEVERITY_ERROR === $severity ) {
					$issues[ $owner ][ $key ]['severity'] = $severity;
				}
				continue;
			}

			$issues[ $owner ][ $key ] = [
				'issue'      => (string) $row['issue'],
				'code'       => (string) $row['code'],
				'action'     => (string) $row['action'],
				'action_url' => (string) $row['action_url'],
				'severity'   => $severity,
				'source'     => 'merchant_center',
			];
		}

		foreach ( $issues as $owner => $product_issues ) {
			$product_issues = array_values( $product_issues );
			// Errors first, keeping the query order within each severity.
			usort(
				$product_issues,
				function ( $a, $b ) {
					return (int) ( MerchantStatuses::SEVERITY_ERROR !== $a['severity'] ) <=> (int) ( MerchantStatuses::SEVERITY_ERROR !== $b['severity'] );
				}
			);
			$issues[ $owner ] = $product_issues;
		}

		return $issues;
	}

	/**
	 * Turn a product's local sync errors into issue entries, worded and coded like
	 * the pre-sync issues of the Merchant Center issues list.
	 *
	 * @param array $errors Errors as returned by ProductHelper::get_validation_errors().
	 *
	 * @return array[]
	 */
	protected function get_sync_error_issues( array $errors ): array {
		/** @var MerchantStatuses $merchant_statuses */
		$merchant_statuses = $this->container->get( MerchantStatuses::class );

		$issues = [];
		foreach ( $errors as $error ) {
			if ( ! is_string( $error ) || '' === trim( $error ) ) {
				continue;
			}

			$issue    = $merchant_statuses->get_presync_issue( $error );
			$issues[] = [
				'issue'      => $issue['issue'],
				'code'       => $issue['code'],
				'action'     => $issue['action'],
				'action_url' => $issue['action_url'],
				'severity'   => MerchantStatuses::SEVERITY_ERROR,
				'source'     => 'sync',
			];
		}

		return $issues;
	}

	/**
	 * Count the number of products (using the title, journey and visibility filters if present).
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return int
	 *
	 * @throws InvalidValue If the orderby or visibility value isn't valid.
	 */
	public function count( WP_REST_Request $request ): int {
		$this->request = $request;
		$args          = $this->prepare_query_args();

		add_filter( 'posts_where', [ $this, 'filter_posts_where' ], 10, 2 );
		try {
			$ids = $this->product_repository->find_ids( $args );
		} finally {
			remove_filter( 'posts_where', [ $this, 'filter_posts_where' ] );
		}

		return count( $ids );
	}

	/**
	 * Prepare the args to be used to retrieve the products, namely orderby, meta_query and type.
	 *
	 * @return array
	 *
	 * @throws InvalidValue If the orderby or visibility value isn't valid.
	 */
	protected function prepare_query_args(): array {
		// The same type list as the status tab counts, so a tab's count equals its rows.
		/** @var ProductJourney $journey */
		$journey       = $this->container->get( ProductJourney::class );
		$product_types = $journey->get_product_types();

		$args = [
			'type'    => $product_types,
			'status'  => 'publish',
			'orderby' => [ 'title' => 'ASC' ],
		];

		if ( ! empty( $this->request['ids'] ) ) {
			$args['include'] = explode( ',', $this->request['ids'] );
		}

		if ( ! empty( $this->request['search'] ) ) {
			$args['gla_search'] = $this->request['search'];
		}

		if ( ! empty( $this->request['journey'] ) ) {
			// Applied by journey_filter() inside the product query itself.
			$args['gla_journey'] = (string) $this->request['journey'];
		}

		if ( ! empty( $this->request['visibility'] ) ) {
			$visibility = $this->request['visibility'];
			if ( ! in_array( $visibility, ChannelVisibility::ALLOWED_VALUES, true ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at the output boundary.
				throw InvalidValue::not_in_allowed_list( 'visibility', ChannelVisibility::ALLOWED_VALUES );
			}

			// Applied by visibility_filter() inside the product query itself, on the
			// effective visibility: products hidden from the store catalog are not synced,
			// so they count as "Don't sync and show" whatever their setting.
			$args['gla_visibility'] = $visibility;
		}

		if ( empty( $this->request['orderby'] ) ) {
			return $args;
		}

		switch ( $this->request['orderby'] ) {
			case 'title':
				$args['orderby']['title'] = $this->get_order();
				break;
			case 'id':
				$args['orderby'] = [ 'ID' => $this->get_order() ] + $args['orderby'];
				break;
			case 'visible':
				$args['meta_key'] = $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY );
				$args['orderby']  = [ 'meta_value' => $this->get_order() ] + $args['orderby'];
				break;
			case 'status':
				$args['meta_key'] = $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS );
				$args['orderby']  = [ 'meta_value' => $this->get_order() ] + $args['orderby'];
				break;
			case 'total_sales':
				$args['meta_key'] = self::META_KEY_TOTAL_SALES;
				$args['orderby']  = [ 'meta_value_num' => $this->get_order() ] + $args['orderby'];
				break;
			default:
				throw InvalidValue::not_in_allowed_list( 'orderby', [ 'title', 'id', 'visible', 'status', 'total_sales' ] );
		}

		return $args;
	}

	/**
	 * Convert the per_page and page parameters into limit and offset values.
	 *
	 * @return array Containing limit and offset values.
	 */
	protected function prepare_query_pagination(): array {
		$limit  = -1;
		$offset = 0;

		if ( ! empty( $this->request['per_page'] ) ) {
			$limit  = intval( $this->request['per_page'] );
			$page   = max( 1, intval( $this->request['page'] ) );
			$offset = $limit * ( $page - 1 );
		}
		return [ $limit, $offset ];
	}

	/**
	 * The posts_where callback added around this helper's product queries.
	 *
	 * Hook data is untrusted: the hook may be fired without a WP_Query, and another
	 * callback may have returned a non-string WHERE clause. That clause has already
	 * lost its conditions, so it is not repaired into a string (which would widen the
	 * query): queries carrying this helper's search or status filter fail closed with
	 * `AND 1 = 0`, and any other query gets the value back untouched.
	 *
	 * @internal Hooked by this class around its own queries only.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $where    The WHERE clause of the query.
	 * @param mixed $wp_query The WP_Query instance.
	 *
	 * @return mixed The updated WHERE clause.
	 */
	public function filter_posts_where( $where, $wp_query = null ) {
		if ( ! $wp_query instanceof WP_Query ) {
			return $where;
		}

		if ( ! is_string( $where ) ) {
			return $this->has_feed_query_vars( $wp_query ) ? ' AND 1 = 0' : $where;
		}

		return $this->visibility_filter( $this->journey_filter( $this->title_filter( $where, $wp_query ), $wp_query ), $wp_query );
	}

	/**
	 * Whether a query carries this helper's search or status filter.
	 *
	 * @param WP_Query $wp_query
	 *
	 * @return bool
	 */
	protected function has_feed_query_vars( WP_Query $wp_query ): bool {
		return '' !== $wp_query->get( 'gla_search' ) || '' !== $wp_query->get( 'gla_journey' ) || '' !== $wp_query->get( 'gla_visibility' );
	}

	/**
	 * Filter for the posts_where hook, adds WHERE clause to search
	 * for the 'search' parameter in the product titles (when present).
	 *
	 * @param string   $where The WHERE clause of the query.
	 * @param WP_Query $wp_query The WP_Query instance (passed by reference).
	 *
	 * @return string The updated WHERE clause.
	 */
	public function title_filter( string $where, WP_Query $wp_query ): string {
		$gla_search = $wp_query->get( 'gla_search' );
		if ( $gla_search ) {
			$title_search = '%' . $this->wpdb->esc_like( $gla_search ) . '%';
			$where       .= $this->wpdb->prepare( " AND `{$this->wpdb->posts}`.`post_title` LIKE %s", $title_search ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return $where;
	}

	/**
	 * Filter for the posts_where hook, limits the products to one sync status (the
	 * `journey` parameter) when present, with the same SQL that counts the status tabs.
	 *
	 * @internal Called by filter_posts_where().
	 *
	 * @since x.x.x
	 *
	 * @param mixed $where    The WHERE clause of the query.
	 * @param mixed $wp_query The WP_Query instance.
	 *
	 * @return mixed The updated WHERE clause.
	 */
	public function journey_filter( $where, $wp_query = null ) {
		if ( ! $wp_query instanceof WP_Query || ! is_string( $where ) ) {
			return $where;
		}

		$segment = $wp_query->get( 'gla_journey' );
		if ( '' === $segment || null === $segment ) {
			return $where;
		}

		/** @var ProductJourney $journey */
		$journey = $this->container->get( ProductJourney::class );
		$ids_sql = is_string( $segment ) ? $journey->get_product_ids_sql( $segment ) : null;

		// An unknown or malformed status matches nothing rather than every product.
		return $where . ( null === $ids_sql ? ' AND 1 = 0' : " AND `{$this->wpdb->posts}`.`ID` IN ( {$ids_sql} )" );
	}

	/**
	 * Filter for the posts_where hook, limits the products to one effective channel
	 * visibility (the `visibility` parameter) when present, with the same SQL condition
	 * that puts catalog-hidden products in the "not synced" status.
	 *
	 * @internal Called by filter_posts_where().
	 *
	 * @since x.x.x
	 *
	 * @param mixed $where    The WHERE clause of the query.
	 * @param mixed $wp_query The WP_Query instance.
	 *
	 * @return mixed The updated WHERE clause.
	 */
	public function visibility_filter( $where, $wp_query = null ) {
		if ( ! $wp_query instanceof WP_Query || ! is_string( $where ) ) {
			return $where;
		}

		$visibility = $wp_query->get( 'gla_visibility' );
		if ( '' === $visibility || null === $visibility ) {
			return $where;
		}

		// A malformed value matches nothing rather than every product.
		if ( ! in_array( $visibility, ChannelVisibility::ALLOWED_VALUES, true ) ) {
			return $where . ' AND 1 = 0';
		}

		/** @var ProductJourney $journey */
		$journey = $this->container->get( ProductJourney::class );

		return $where . ' AND ' . $journey->get_effective_visibility_sql( $visibility, "`{$this->wpdb->posts}`.`ID`" );
	}

	/**
	 * Return the ORDER BY order based on the order request parameter value.
	 *
	 * @return string
	 */
	protected function get_order(): string {
		return strtoupper( $this->request['order'] ?? '' ) === 'DESC' ? 'DESC' : 'ASC';
	}
}
