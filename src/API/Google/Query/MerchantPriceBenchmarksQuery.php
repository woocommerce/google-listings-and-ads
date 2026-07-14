<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantPriceBenchmarksQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantPriceBenchmarksQuery extends MapiReportQuery {

	use ReportQueryTrait;

	/**
	 * MerchantPriceBenchmarksQuery constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'price_competitiveness_product_view' );

		$this->set_initial_columns();
		$this->handle_query_args( $args );
	}

	/**
	 * Filter the query by a list of product IDs.
	 *
	 * @param array $ids List of product IDs to filter by.
	 *
	 * @return $this
	 */
	public function filter( array $ids ): QueryInterface {
		if ( ! empty( $ids ) ) {
			$this->where( 'price_competitiveness_product_view.id', $ids, 'IN' );
		}
		return $this;
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'id'              => 'price_competitiveness_product_view.id',
				'offer_id'        => 'price_competitiveness_product_view.offer_id',
				'title'           => 'price_competitiveness_product_view.title',
				'price'           => 'price_competitiveness_product_view.price',
				'country_code'    => 'price_competitiveness_product_view.report_country_code',
				'benchmark_price' => 'price_competitiveness_product_view.benchmark_price',
			]
		);
	}
}
