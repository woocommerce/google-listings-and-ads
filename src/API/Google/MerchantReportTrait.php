<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantReportQuery;
use Exception;
use Google\Exception as GoogleException;
use Google_Service_ShoppingContent_ReportRow as ReportRow;
use Google_Service_ShoppingContent_SearchRequest as SearchRequest;

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
		$metrics = $this->get_report_row_metrics( $row, $args );
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
}
