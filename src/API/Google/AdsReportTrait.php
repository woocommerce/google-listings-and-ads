<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsReportQuery;
use DateTime;
use Exception;
use Google\Ads\GoogleAds\V6\Common\Segments;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;

/**
 * Trait AdsReportTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait AdsReportTrait {

	/** @var array $report_data */
	private $report_data = [];

	/**
	 * Get report data for campaigns.
	 *
	 * @param string $type Report type (campaigns or products).
	 * @param array  $args Query arguments.
	 *
	 * @return array
	 * @throws Exception If the report data can't be retrieved.
	 */
	public function get_report_data( string $type, array $args ): array {
		try {
			$results = $this->query(
				( new AdsReportQuery( $type ) )
				->fields( $args['fields'] )
				->segment_interval( $args['interval'] )
				->where( 'segments.date', [ $args['after'], $args['before'] ], 'BETWEEN' )
				->get_query()
			);

			foreach ( $results->iterateAllElements() as $row ) {
				$this->add_report_row( $type, $row, $args );
			}

			// Remove index from arrays to conform to schema.
			foreach ( [ 'products', 'campaigns', 'intervals' ] as $key ) {
				if ( isset( $this->report_data[ $key ] ) ) {
					$this->report_data[ $key ] = array_values( $this->report_data[ $key ] );
				}
			}

			return $this->report_data;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Unable to retrieve campaign report data: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ) );
		}
	}

	/**
	 * Add data for a report row.
	 *
	 * @param string       $type Report type (campaigns or products).
	 * @param GoogleAdsRow $row  Report row.
	 * @param array        $args Request arguments.
	 */
	protected function add_report_row( string $type, GoogleAdsRow $row, array $args ) {
		$campaign = $row->getCampaign();
		$segments = $row->getSegments();
		$metrics  = $this->get_report_row_metrics( $row, $args );

		if ( 'products' === $type && $segments ) {
			$product_id = $segments->getProductItemId();
			$this->increase_report_data(
				'products',
				(string) $product_id,
				[
					'id'        => $product_id,
					'name'      => $segments->getProductTitle(),
					'subtotals' => $metrics,
				]
			);
		}

		if ( 'campaigns' === $type && $campaign ) {
			$campaign_id = $campaign->getId();
			$this->increase_report_data(
				'campaigns',
				(string) $campaign_id,
				[
					'id'        => $campaign_id,
					'name'      => $campaign->getName(),
					'status'    => CampaignStatus::label( $campaign->getStatus() ),
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
	 * @param GoogleAdsRow $row  Report row.
	 * @param array        $args Request arguments.
	 */
	protected function get_report_row_metrics( GoogleAdsRow $row, array $args ) {
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
				case 'spend':
					$data['spend'] = $this->from_micro( $metrics->getCostMicros() );
					break;
				case 'sales':
					$data['sales'] = $metrics->getConversionsValue();
					break;
				case 'conversions':
					$data['conversions'] = $metrics->getConversions();
					break;
			}
		}

		return $data;
	}

	/**
	 * Increase report data by adding the subtotals.
	 *
	 * @param string $field Field to increase.
	 * @param string $index Unique index.
	 * @param array  $data  Report data.
	 */
	protected function increase_report_data( string $field, string $index, array $data ) {
		if ( ! isset( $this->report_data[ $field ][ $index ] ) ) {
			$this->report_data[ $field ][ $index ] = $data;
		} elseif ( ! empty( $data['subtotals'] ) ) {
			foreach ( $data['subtotals'] as $name => $subtotal ) {
				$this->report_data[ $field ][ $index ]['subtotals'][ $name ] += $subtotal;
			}
		}
	}

	/**
	 * Increase report totals.
	 *
	 * @param array $data Totals data.
	 */
	protected function increase_report_totals( array $data ) {
		foreach ( $data as $name => $total ) {
			if ( ! isset( $this->report_data['totals'][ $name ] ) ) {
				$this->report_data['totals'][ $name ] = $total;
			} else {
				$this->report_data['totals'][ $name ] += $total;
			}
		}
	}

	/**
	 * Get a unique interval index based on the segments data.
	 *
	 * Types:
	 * day     = <year>-<month>-<day>
	 * week    = <year>-<weeknumber>
	 * month   = <year>-<month>
	 * quarter = <year>-<quarter>
	 * year    = <year>
	 *
	 * @param string   $interval Interval type.
	 * @param Segments $segments Report segment data.
	 *
	 * @return string
	 * @throws Exception When invalid interval type is given.
	 */
	protected function get_segment_interval( string $interval, Segments $segments ): string {
		switch ( $interval ) {
			case 'day':
				$date = new DateTime( $segments->getDate() );
				break;
			case 'week':
				$date = new DateTime( $segments->getWeek() );
				break;
			case 'month':
				$date = new DateTime( $segments->getMonth() );
				break;
			case 'quarter':
				$date = new DateTime( $segments->getQuarter() );
				break;
			case 'year':
				$date = DateTime::createFromFormat( 'Y', (string) $segments->getYear() );
				break;
			default:
				throw new Exception( __( 'Invalid interval', 'google-listings-and-ads' ) );
		}
		return TimeInterval::time_interval_id( $interval, $date );
	}
}
