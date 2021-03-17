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
	 */
	public function __construct() {
		parent::__construct( 'MerchantPerformanceView' );
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

		$this->add_columns(
			array_map(
				function( $field ) use ( $map ) {
					return $map[ $field ] ?? null;
				},
				$fields
			)
		);

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

		$this->add_columns(
			[
				$map[ $interval ] ?? null,
			]
		);

		return $this;
	}
}
