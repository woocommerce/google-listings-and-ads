<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantQuery;

/**
 * Class MerchantPriceBenchmarksProductReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantPriceBenchmarksProductReportQuery extends MerchantQuery {

	use ReportQueryTrait;

	/**
	 * MerchantPriceBenchmarksProductReportQuery constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'MerchantPerformanceView' );

		$this->set_initial_columns();
		$this->handle_query_args( $args );
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'offer_id'    => 'segments.offer_id',
				'clicks'      => 'metrics.clicks',
				'impressions' => 'metrics.impressions',
				'ctr'         => 'metrics.ctr',
				'conversions' => 'metrics.conversions',
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

		return $this->where( 'segments.offer_id', $ids, 'IN' );
	}
}
