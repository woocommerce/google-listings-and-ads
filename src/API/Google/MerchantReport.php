<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantFreeListingReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantProductReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use DateTime;
use Exception;
use Google\Exception as GoogleException;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_ReportRow as ReportRow;
use Google_Service_ShoppingContent_Segments as Segments;

/**
 * Trait MerchantReportTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantReport implements OptionsAwareInterface {

	use OptionsAwareTrait;
	use ReportTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingService
	 */
	protected $service;

	/**
	 * Merchant Report constructor.
	 *
	 * @param ShoppingService $service
	 */
	public function __construct( ShoppingService $service ) {
		$this->service = $service;
	}

	/**
	 * Get report data for free listings.
	 *
	 * @param string $type Report type (free_listings or products).
	 * @param array  $args Query arguments.
	 *
	 * @return array
	 * @throws Exception If the report data can't be retrieved.
	 */
	public function get_report_data( string $type, array $args ): array {
		try {
			if ( 'products' === $type ) {
				$query = new MerchantProductReportQuery( $args );
			} else {
				$query = new MerchantFreeListingReportQuery( $args );
			}

			$results = $query
				->set_client( $this->service, $this->options->get_merchant_id() )
				->get_results();

			$this->init_report_totals( $args['fields'] ?? [] );

			foreach ( $results->getResults() as $row ) {
				$this->add_report_row( $type, $row, $args );
			}

			if ( $results->getNextPageToken() ) {
				$this->report_data['next_page'] = $results->getNextPageToken();
			}

			$this->remove_report_indexes( [ 'products', 'free_listings', 'intervals' ] );

			return $this->report_data;
		} catch ( GoogleException $e ) {
			do_action( 'gla_mc_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to retrieve report data.', 'google-listings-and-ads' ), $e->getCode() );
		}
	}

	/**
	 * Add data for a report row.
	 *
	 * @param string    $type Report type (free_listings or products).
	 * @param ReportRow $row  Report row.
	 * @param array     $args Request arguments.
	 */
	protected function add_report_row( string $type, ReportRow $row, array $args ) {
		$segments = $row->getSegments();
		$metrics  = $this->get_report_row_metrics( $row, $args );

		if ( 'free_listings' === $type ) {
			$this->increase_report_data(
				'free_listings',
				'free',
				[
					'subtotals' => $metrics,
				]
			);
		}

		if ( 'products' === $type && $segments ) {
			$product_id = $segments->getOfferId();
			$this->increase_report_data(
				'products',
				(string) $product_id,
				[
					'id'        => $product_id,
					'subtotals' => $metrics,
				]
			);
		}

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
					$data['clicks'] = (int) $metrics->getClicks();
					break;
				case 'impressions':
					$data['impressions'] = (int) $metrics->getImpressions();
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
	 * @throws InvalidValue When invalid interval type is given.
	 */
	protected function get_segment_interval( string $interval, Segments $segments ): string {
		if ( 'day' !== $interval ) {
			throw InvalidValue::not_in_allowed_list( $interval, [ 'day' ] );
		}

		$date = $segments->getDate();
		$date = new DateTime( "{$date->getYear()}-{$date->getMonth()}-{$date->getDay()}" );
		return TimeInterval::time_interval_id( $interval, $date );
	}
}
