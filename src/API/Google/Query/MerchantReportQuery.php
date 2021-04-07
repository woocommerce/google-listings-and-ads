<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class MerchantReportQuery extends MerchantQuery {

	use ReportQueryTrait;

	/**
	 * Type of report (free_listings or products).
	 *
	 * @var string
	 */
	protected $type = 'free_listings';

	/**
	 * Query constructor.
	 *
	 * @param string $type Report type (campaigns or products).
	 * @param array  $args Query arguments.
	 */
	public function __construct( string $type, array $args ) {
		$this->type = $type;
		parent::__construct( 'MerchantPerformanceView' );

		if ( 'products' === $this->type ) {
			$this->columns(
				[
					'id' => 'segments.offer_id',
				]
			);
		}

		$this->handle_query_args( $args );
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

	/**
	 * Filter the query by a list of ID's.
	 *
	 * @param array $ids list of ID's to filter by.
	 *
	 * @return $this
	 */
	public function filter( array $ids ): QueryInterface {
		if ( empty( $ids ) || 'products' !== $this->type ) {
			return $this;
		}

		return $this->where( 'segments.offer_id', $ids, 'IN' );
	}
}
