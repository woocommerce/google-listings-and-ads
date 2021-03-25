<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantReportQuery;
use DateTime;
use Exception;
use Google\Exception as GoogleException;
use Google_Service_ShoppingContent_ReportRow as ReportRow;
use Google_Service_ShoppingContent_SearchRequest as SearchRequest;
use Google_Service_ShoppingContent_Segments as Segments;

/**
 * Trait MerchantReportTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait MerchantReportTrait {

	use ReportTrait;

	/**
	 * Get report data for free listings.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 * @throws Exception If the report data can't be retrieved.
	 */
	public function get_report_data( array $args ): array {
		try {
			$request = new SearchRequest();
			$request->setQuery( ( new MerchantReportQuery( $args ) )->get_query() );

			$results = $this->service->reports->search( $this->get_id(), $request );

			foreach ( $results->getResults() as $row ) {
				$this->add_report_row( $row, $args );
			}

			$this->remove_report_indexes( [ 'intervals' ] );

			return $this->report_data;
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve report data.', 'google-listings-and-ads' ), $e->getCode() );
		}
	}

	/**
	 * Add data for a report row.
	 *
	 * @param ReportRow $row  Report row.
	 * @param array     $args Request arguments.
	 */
	protected function add_report_row( ReportRow $row, array $args ) {
		$segments = $row->getSegments();
		$metrics  = $this->get_report_row_metrics( $row, $args );

		if ( $segments && ! empty( $args['interval'] ) ) {
			$interval = $this->get_segment_interval( $args['interval'], $segments );

			$this->increase_report_data(
				'intervals',
				$interval,
				[
					'interval'  => $interval,
					'subtotals' => $metrics,
				]
			);
		}

		$this->increase_report_totals( $metrics );
	}

	/**
	 * Get metrics for a report row.
	 *
	 * @param ReportRow $row  Report row.
	 * @param array     $args Request arguments.
	 */
	protected function get_report_row_metrics( ReportRow $row, array $args ) {
		$metrics = $row->getMetrics();

		if ( ! $metrics || empty( $args['fields'] ) ) {
			return [];
		}

		$data = [];
		foreach ( $args['fields'] as $field ) {
			switch ( $field ) {
				case 'clicks':
					$data['clicks'] = $metrics->getClicks();
					break;
				case 'impressions':
					$data['impressions'] = $metrics->getImpressions();
					break;
			}
		}

		return $data;
	}

	/**
	 * Get a unique interval index based on the segments data.
	 *
	 * Types:
	 * day     = <year>-<month>-<day>
	 *
	 * @param string   $interval Interval type.
	 * @param Segments $segments Report segment data.
	 *
	 * @return string
	 * @throws Exception When invalid interval type is given.
	 */
	protected function get_segment_interval( string $interval, Segments $segments ): string {
		if ( 'day' !== $interval ) {
			throw new Exception( __( 'Invalid interval', 'google-listings-and-ads' ) );
		}

		$date = $segments->getDate();
		$date = new DateTime( "{$date->getYear()}-{$date->getMonth()}-{$date->getDay()}" );
		return TimeInterval::time_interval_id( $interval, $date );
	}
}
