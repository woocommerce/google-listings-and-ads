<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
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
	 * @param array $args Query arguments.
	 *
	 * @return array
	 * @throws Exception If the report data can't be retrieved.
	 */
	public function get_report_data( array $args ): array {
		try {
			$this->report_data = [
				'campaigns' => [],
				'intervals' => [],
				'spend'     => 0,
				'sales'     => 0,
			];

			$response = $this->query( $this->get_report_query( $args ) );
			foreach ( $response->iterateAllElements() as $row ) {
				$this->add_report_row( $row, $args['interval'] ?? '' );
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
	 * @param GoogleAdsRow $row      Report row.
	 * @param string       $interval Segment interval.
	 */
	protected function add_report_row( GoogleAdsRow $row, string $interval ) {
		$campaign_spend = 0;
		$campaign_sales = 0;

		$campaign = $row->getCampaign();
		$metrics  = $row->getMetrics();
		$segments = $row->getSegments();

		if ( $metrics ) {
			$campaign_spend = $this->from_micro( $metrics->getCostMicros() );
			$campaign_sales = $metrics->getConversionsValue();
		}

		if ( $segments && ! empty( $interval ) ) {
			$index = $this->get_segment_interval( $interval, $segments );

			if ( ! isset( $this->report_data['intervals'][ $index ] ) ) {
				$this->report_data['intervals'][ $index ] = [
					'interval'  => $index,
					'subtotals' => [
						'spend' => $campaign_spend,
						'sales' => $campaign_sales,
					],
				];
			} else {
				$this->report_data['intervals'][ $index ]['subtotals']['spend'] += $campaign_spend;
				$this->report_data['intervals'][ $index ]['subtotals']['sales'] += $campaign_sales;
			}
		}

		$campaign_id = $campaign->getId();
		if ( ! isset( $this->report_data['campaigns'][ $campaign_id ] ) ) {
			$this->report_data['campaigns'][ $campaign_id ] = [
				'id'        => $campaign_id,
				'name'      => $campaign->getName(),
				'status'    => CampaignStatus::label( $campaign->getStatus() ),
				'subtotals' => [
					'spend' => $campaign_spend,
					'sales' => $campaign_sales,
				],
			];
		} else {
			$this->report_data['campaigns'][ $campaign_id ]['subtotals']['spend'] += $campaign_spend;
			$this->report_data['campaigns'][ $campaign_id ]['subtotals']['sales'] += $campaign_sales;
		}

		$this->report_data['spend'] += $campaign_spend;
		$this->report_data['sales'] += $campaign_sales;
	}

	/**
	 * Get report query.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return string
	 */
	protected function get_report_query( array $args ): string {
		$fields = [
			'campaign.id',
			'campaign.name',
			'campaign.status',
			'metrics.cost_micros',
			'metrics.conversions_value',
		];

		$segments_field = [
			'day'     => 'segments.date',
			'week'    => 'segments.week',
			'month'   => 'segments.month',
			'quarter' => 'segments.quarter',
			'year'    => 'segments.year',
		];

		if ( ! empty( $args['interval'] ) && array_key_exists( $args['interval'], $segments_field ) ) {
			$fields[] = $segments_field[ $args['interval'] ];
		}

		$condition = [
			'key'      => 'segments.date',
			'operator' => 'BETWEEN',
			'value'    => [
				$args['after'],
				$args['before'],
			],
		];

		return $this->build_query( $fields, 'campaign', [ $condition ] );
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
