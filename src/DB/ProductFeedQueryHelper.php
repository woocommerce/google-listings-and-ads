<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

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
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
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
 * - MerchantStatuses
 * - ProductHelper
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
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 *
	 * @throws InvalidValue If the orderby value isn't valid.
	 * @throws Exception If the status data can't be retrieved from Google.
	 */
	public function get( WP_REST_Request $request ): array {
		$this->request          = $request;
		$products               = [];
		$args                   = $this->prepare_query_args();
		list( $limit, $offset ) = $this->prepare_query_pagination();

		$mc_service = $this->container->get( MerchantCenterService::class );
		if ( $mc_service->is_connected() ) {
			$this->container->get( MerchantStatuses::class )->maybe_refresh_status_data();
		}

		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		add_filter( 'posts_where', [ $this, 'title_filter' ], 10, 2 );

		foreach ( $this->product_repository->find( $args, $limit, $offset ) as $product ) {
			$id     = $product->get_id();
			$errors = $product_helper->get_validation_errors( $product );

			$products[ $id ] = [
				'id'        => $id,
				'title'     => $product->get_name(),
				'visible'   => $product_helper->get_channel_visibility( $product ) !== ChannelVisibility::DONT_SYNC_AND_SHOW,
				'status'    => $product_helper->get_mc_status( $product ) ?: $product_helper->get_sync_status( $product ),
				'image_url' => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
				'price'     => $product->get_price(),
				'errors'    => array_values( $errors ),
			];
		}

		remove_filter( 'posts_where', [ $this, 'title_filter' ] );

		return array_values( $products );
	}

	/**
	 * Count the number of products (using title filter if present).
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
		$ids = $this->product_repository->find_ids( $args );
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
		$product_types = ProductSyncer::get_supported_product_types();
		$product_types = array_diff( $product_types, [ 'variation' ] );

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
	 * Return the ORDER BY order based on the order request parameter value.
	 *
	 * @return string
	 */
	protected function get_order(): string {
		return strtoupper( $this->request['order'] ?? '' ) === 'DESC' ? 'DESC' : 'ASC';
	}
}

