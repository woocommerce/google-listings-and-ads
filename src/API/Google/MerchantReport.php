<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantFreeListingReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantProductReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantProductViewReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use DateTime;
use Exception;

/**
 * Trait MerchantReportTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantReport implements OptionsAwareInterface {

	use OptionsAwareTrait;
	use ReportTrait;

	/**
	 * Product helper class.
	 *
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * Merchant API client.
	 *
	 * @var MerchantApiClient
	 */
	protected $mapi_client;

	/**
	 * Merchant Report constructor.
	 *
	 * @param ProductHelper     $product_helper
	 * @param MerchantApiClient $mapi_client
	 */
	public function __construct( ProductHelper $product_helper, MerchantApiClient $mapi_client ) {
		$this->product_helper = $product_helper;
		$this->mapi_client    = $mapi_client;
	}

	/**
	 * Get ProductView Query response.
	 *
	 * @param string|null $next_page_token The next page token.
	 * @return array Associative array with product statuses and the next page token.
	 *
	 * @throws Exception If the product view report data can't be retrieved.
	 */
	public function get_product_view_report( $next_page_token = null ): array {
		$batch_size = apply_filters( 'woocommerce_gla_product_view_report_page_size', 500 );

		try {
			$product_view_data = [
				'statuses'        => [],
				'next_page_token' => null,
			];

			$query = new MerchantProductViewReportQuery(
				[
					'next_page' => $next_page_token,
					'per_page'  => $batch_size,
				]
			);

			$response = $query
			->set_client( $this->mapi_client, $this->options->get_merchant_id() )
			->get_results();

			foreach ( $response['results'] ?? [] as $row ) {
				$product_view = $row['productView'] ?? [];
				$mc_id        = $product_view['id'] ?? '';

				$wc_product_id     = $this->product_helper->get_wc_product_id( $mc_id );
				$mc_product_status = $this->convert_aggregated_status_to_mc_status( $product_view['aggregatedReportingContextStatus'] ?? '' );

				// Skip if the product id does not exist
				if ( ! $wc_product_id ) {
					continue;
				}

				$product_view_data['statuses'][ $wc_product_id ] = [
					'mc_id'           => $mc_id,
					'product_id'      => $wc_product_id,
					'status'          => $mc_product_status,
					'expiration_date' => $this->convert_mapi_date( $product_view['expirationDate'] ?? [] ),
				];
			}

			$product_view_data['next_page_token'] = $response['nextPageToken'] ?? null;

			return $product_view_data;
		} catch ( MerchantApiException $e ) {
			// The woocommerce_gla_mc_client_exception action is fired by MerchantApiException::__construct();
			// the call-site context differs from the pre-MAPI code (now MapiReportQuery::query_results).
			throw new Exception( __( 'Unable to retrieve Product View Report.', 'google-listings-and-ads' ) . $e->getMessage(), $e->get_http_status() );
		}
	}

	/**
	 * Convert the product view aggregated status to the MC status.
	 *
	 * @param string $status The aggregated status of the product.
	 *
	 * @return string The MC status.
	 */
	protected function convert_aggregated_status_to_mc_status( string $status ): string {
		switch ( $status ) {
			case 'ELIGIBLE':
				return MCStatus::APPROVED;
			case 'ELIGIBLE_LIMITED':
				return MCStatus::PARTIALLY_APPROVED;
			case 'NOT_ELIGIBLE_OR_DISAPPROVED':
				return MCStatus::DISAPPROVED;
			case 'PENDING':
				return MCStatus::PENDING;
			default:
				return MCStatus::NOT_SYNCED;
		}
	}

	/**
	 * Convert a Merchant API date object ({year, month, day}) to a DateTime.
	 *
	 * @param array $date
	 *
	 * @return DateTime|null
	 */
	protected function convert_mapi_date( array $date ) {
		if ( empty( $date ) ) {
			return null;
		}

		return DateTime::createFromFormat(
			'Y-m-d|',
			sprintf( '%d-%d-%d', $date['year'] ?? 0, $date['month'] ?? 0, $date['day'] ?? 0 )
		);
	}


	/**
	 * Get report data for product feed.
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
				->set_client( $this->mapi_client, $this->options->get_merchant_id() )
				->get_results();

			$this->init_report_totals( $args['fields'] ?? [] );

			foreach ( $results['results'] ?? [] as $row ) {
				$this->add_report_row( $type, $row, $args );
			}

			if ( ! empty( $results['nextPageToken'] ) ) {
				$this->report_data['next_page'] = $results['nextPageToken'];
			}

			// Sort intervals to generate an ordered graph.
			if ( isset( $this->report_data['intervals'] ) ) {
				ksort( $this->report_data['intervals'] );
			}

			$this->remove_report_indexes( [ 'products', 'free_listings', 'intervals' ] );

			return $this->report_data;
		} catch ( MerchantApiException $e ) {
			// The woocommerce_gla_mc_client_exception action is fired by MerchantApiException::__construct();
			// the call-site context differs from the pre-MAPI code (now MapiReportQuery::query_results).
			throw new Exception( __( 'Unable to retrieve report data.', 'google-listings-and-ads' ), $e->get_http_status() );
		}
	}

	/**
	 * Add data for a report row.
	 *
	 * @param string $type Report type (free_listings or products).
	 * @param array  $row  Report row.
	 * @param array  $args Request arguments.
	 */
	protected function add_report_row( string $type, array $row, array $args ) {
		$view    = $row['productPerformanceView'] ?? [];
		$metrics = $this->get_report_row_metrics( $view, $args );

		if ( 'free_listings' === $type ) {
			$this->increase_report_data(
				'free_listings',
				'free',
				[
					'subtotals' => $metrics,
				]
			);
		}

		if ( 'products' === $type && ! empty( $view['offerId'] ) ) {
			$product_id = $view['offerId'];
			$this->increase_report_data(
				'products',
				(string) $product_id,
				[
					'id'        => $product_id,
					'subtotals' => $metrics,
				]
			);

			// Retrieve product title and add to report.
			if ( empty( $this->report_data['products'][ $product_id ]['name'] ) ) {
				$name = $this->product_helper->get_wc_product_title( (string) $product_id );
				$this->report_data['products'][ $product_id ]['name'] = $name;
			}
		}

		if ( ! empty( $view ) && ! empty( $args['interval'] ) ) {
			$interval = $this->get_segment_interval( $args['interval'], $view['date'] ?? [] );

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
	 * @param array $view Product performance view data.
	 * @param array $args Request arguments.
	 *
	 * @return array
	 */
	protected function get_report_row_metrics( array $view, array $args ): array {
		if ( empty( $args['fields'] ) ) {
			return [];
		}

		$data = [];
		foreach ( $args['fields'] as $field ) {
			switch ( $field ) {
				case 'clicks':
					$data['clicks'] = (int) ( $view['clicks'] ?? 0 );
					break;
				case 'impressions':
					$data['impressions'] = (int) ( $view['impressions'] ?? 0 );
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
	 * @param string $interval Interval type.
	 * @param array  $date     Date object ({year, month, day}).
	 *
	 * @return string
	 * @throws InvalidValue When invalid interval type is given.
	 */
	protected function get_segment_interval( string $interval, array $date ): string {
		if ( 'day' !== $interval ) {
			throw InvalidValue::not_in_allowed_list( $interval, [ 'day' ] );
		}

		$date = DateTime::createFromFormat(
			'Y-m-d|',
			sprintf( '%d-%d-%d', $date['year'] ?? 0, $date['month'] ?? 0, $date['day'] ?? 0 )
		);
		return TimeInterval::time_interval_id( $interval, $date );
	}
}
