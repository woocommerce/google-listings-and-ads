<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WP_Query;
use WP_REST_Request;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductFeedQueryHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductFeedQueryHelper implements Service, ContainerAwareInterface {

	use ContainerAwareTrait;

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * @var WP_REST_Request
	 */
	protected $request;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * ProductFeedQueryHelper constructor.
	 *
	 * @param wpdb               $wpdb
	 * @param ProductHelper      $product_helper
	 * @param ProductMetaHandler $meta_handler
	 */
	public function __construct( wpdb $wpdb, ProductHelper $product_helper, ProductMetaHandler $meta_handler ) {
		$this->wpdb           = $wpdb;
		$this->product_helper = $product_helper;
		$this->meta_handler   = $meta_handler;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 *
	 * @throws InvalidValue If the orderby value isn't valid.
	 */
	public function get( WP_REST_Request $request ): array {
		$this->request          = $request;
		$products               = [];
		$args                   = $this->prepare_query_args();
		list( $limit, $offset ) = $this->prepare_query_pagination();

		add_filter( 'posts_where', [ $this, 'title_filter' ], 10, 2 );

		foreach ( $this->container->get( ProductRepository::class )->find( $args, $limit, $offset ) as $product ) {
			$id     = $product->get_id();
			$errors = $this->meta_handler->get_errors( $id ) ?: [];

			// Combine errors for variable products, which have a variation-indexed array of errors.
			$first_key = array_key_first( $errors );
			if ( ! empty( $errors ) && is_numeric( $first_key ) && 0 !== $first_key ) {
				$errors = array_unique( array_merge( ...$errors ) );
			}

			$products[ $id ] = [
				'id'      => $id,
				'title'   => $product->get_name(),
				'visible' => $this->product_helper->get_visibility( $product ) !== ChannelVisibility::DONT_SYNC_AND_SHOW,
				'status'  => $this->product_helper->get_sync_status( $product ),
				'errors'  => array_values( $errors ),
			];
		}

		remove_filter( 'posts_where', [ $this, 'title_filter' ] );

		return array_values( $products );
	}

	/**
	 * Count the number of products (including title filter if present)
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return int
	 *
	 * @throws InvalidValue If the orderby value isn't valid.
	 */
	public function count( WP_REST_Request $request ): int {
		$this->request = $request;
		$args          = $this->prepare_query_args();

		add_filter( 'posts_where', [ $this, 'title_filter' ], 10, 2 );
		$ids = $this->container->get( ProductRepository::class )->find_ids( $args );
		remove_filter( 'posts_where', [ $this, 'title_filter' ] );

		return count( $ids );
	}

	/**
	 * Prepare the args to be used to retrieve the products, namely orderby, meta_query and type.
	 *
	 * @return array
	 *
	 * @throws InvalidValue If the orderby value isn't valid.
	 */
	protected function prepare_query_args(): array {
		$args = [
			'type'    => [ 'simple', 'variable' ],
			'orderby' => [ 'title' => 'ASC' ],
		];

		if ( ! empty( $this->request['ids'] ) ) {
			$args['include'] = explode( ',', $this->request['ids'] );
		}

		if ( ! empty( $this->request['search'] ) ) {
			$args['gla_search'] = $this->request['search'];
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
				$args['meta_query'] = [
					'visibility_clause' => [
						'key'     => ProductMetaHandler::KEY_VISIBILITY,
						'compare' => 'EXISTS',
					],
				];
				$args['orderby']    = [ 'visibility_clause' => $this->get_order() ] + $args['orderby'];
				break;
			case 'status':
				$args['meta_query'] = [
					'synced_clause' => [
						'key'     => ProductMetaHandler::KEY_SYNC_STATUS,
						'compare' => 'EXISTS',
					],
				];
				$args['orderby']    = [ 'synced_clause' => $this->get_order() ] + $args['orderby'];
				break;
			default:
				throw InvalidValue::not_in_allowed_list( 'orderby', [ 'title', 'id', 'visible', 'status' ] );
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
	 * Used for the posts_where hook, filters the WHERE clause of the query by
	 * searching for the 'search' parameter in the product titles (when the parameter is present).
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
	 * Return the ORDER BY order based on the order request parameter value.
	 *
	 * @return string
	 */
	protected function get_order(): string {
		return strtoupper( $this->request['order'] ?? '' ) === 'DESC' ? 'DESC' : 'ASC';
	}
}

