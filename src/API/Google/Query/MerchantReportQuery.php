<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
abstract class MerchantReportQuery extends MapiReportQuery {

	use ReportQueryTrait;

	/**
	 * MerchantReportQuery constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'product_performance_view' );

		$this->set_initial_columns();
		$this->handle_query_args( $args );
		$this->where( 'product_performance_view.marketing_method', 'ORGANIC' );
	}

	/**
	 * Add all the requested fields.
	 *
	 * @param array $fields List of fields.
	 *
	 * @return $this
	 */
	public function fields( array $fields ): QueryInterface {
		$map = [
			'clicks'      => 'product_performance_view.clicks',
			'impressions' => 'product_performance_view.impressions',
		];

		$this->add_columns( array_intersect_key( $map, array_flip( $fields ) ) );

		return $this;
	}

	/**
	 * Add a segment interval to the query.
	 *
	 * @param string $interval Type of interval.
	 *
	 * @return $this
	 */
	public function segment_interval( string $interval ): QueryInterface {
		$map = [
			'day'     => 'product_performance_view.date',
			'week'    => 'product_performance_view.week',
			'month'   => 'product_performance_view.month',
			'quarter' => 'product_performance_view.quarter',
			'year'    => 'product_performance_view.year',
		];

		if ( isset( $map[ $interval ] ) ) {
			$this->add_columns( [ $interval => $map[ $interval ] ] );
		}

		return $this;
	}

	/**
	 * Set the initial columns for this query.
	 */
	abstract protected function set_initial_columns();

	/**
	 * Filter the query by a list of ID's.
	 *
	 * @param array $ids list of ID's to filter by.
	 *
	 * @return $this
	 */
	abstract public function filter( array $ids ): QueryInterface;
}
