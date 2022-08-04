<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Google\Ads\GoogleAds\V11\Resources\ShoppingPerformanceView;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
abstract class AdsReportQuery extends AdsQuery {

	use ReportQueryTrait;

	/**
	 * Query constructor.
	 * Uses the resource ShoppingPerformanceView.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'shopping_performance_view' );

		$this->set_initial_columns();
		$this->handle_query_args( $args );
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
			'clicks'      => 'metrics.clicks',
			'impressions' => 'metrics.impressions',
			'spend'       => 'metrics.cost_micros',
			'sales'       => 'metrics.conversions_value',
			'conversions' => 'metrics.conversions',
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
			'day'     => 'segments.date',
			'week'    => 'segments.week',
			'month'   => 'segments.month',
			'quarter' => 'segments.quarter',
			'year'    => 'segments.year',
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
