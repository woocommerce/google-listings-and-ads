<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsReportQuery extends Query {

	/**
	 * Type of report (campaigns or products).
	 *
	 * @var string
	 */
	protected $type = 'campaigns';

	/**
	 * Query constructor.
	 *
	 * @param string $type Report type (campaigns or products).
	 */
	public function __construct( string $type ) {
		$this->type = $type;
		parent::__construct( 'shopping_performance_view' );

		if ( 'products' === $this->type ) {
			$columns = [
				'segments.product_item_id',
				'segments.product_title',
			];
		} else {
			$columns = [
				'campaign.id',
				'campaign.name',
				'campaign.status',
			];
		}

		$this->columns( $columns );
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

		if ( 'products' === $this->type ) {
			$column = 'segments.product_item_id';
		} else {
			$column = 'campaign.id';
		}

		return $this->where( $column, $ids, 'IN' );
	}
}
