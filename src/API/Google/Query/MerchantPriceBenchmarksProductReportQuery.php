<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantPriceBenchmarksProductReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantPriceBenchmarksProductReportQuery extends MapiReportQuery {

	use ReportQueryTrait;

	/**
	 * MerchantPriceBenchmarksProductReportQuery constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'product_performance_view' );

		$this->set_initial_columns();
		$this->handle_query_args( $args );
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'offer_id'    => 'product_performance_view.offer_id',
				'clicks'      => 'product_performance_view.clicks',
				'impressions' => 'product_performance_view.impressions',
				'ctr'         => 'product_performance_view.click_through_rate',
				'conversions' => 'product_performance_view.conversions',
			]
		);
	}

	/**
	 * Filter the query by a list of ID's.
	 *
	 * @param array $ids list of ID's to filter by.
	 *
	 * @return $this
	 */
	public function filter( array $ids ): QueryInterface {
		if ( empty( $ids ) ) {
			return $this;
		}

		return $this->where( 'product_performance_view.offer_id', $ids, 'IN' );
	}
}
