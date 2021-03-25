<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantReportQuery extends Query {

	/**
	 * Query constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( array $args ) {
		parent::__construct( 'MerchantPerformanceView' );

		if ( ! empty( $args['fields'] ) ) {
			$this->fields( $args['fields'] );
		}

		if ( ! empty( $args['interval'] ) ) {
			$this->segment_interval( $args['interval'] );
		}

		if ( ! empty( $args['after'] ) && ! empty( $args['before'] ) ) {
			$this->where( 'segments.date', [ $args['after'], $args['before'] ], 'BETWEEN' );
		}

		if ( ! empty( $args['orderby'] ) ) {
			$this->set_order( $args['orderby'], $args['order'] );
		}

		$this->where( 'segments.program', 'FREE_PRODUCT_LISTING' );
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
}
