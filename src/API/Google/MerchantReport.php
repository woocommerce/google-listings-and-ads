<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantFreeListingReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantProductReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantProductViewReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ReportRow;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Segments;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductView;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\SearchResponse;
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
	use PluginHelper;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingContent
	 */
	protected $service;

	/**
	 * Product repository.
	 *
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * Product helper class.
	 *
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * Merchant Report constructor.
	 *
	 * @param ShoppingContent   $service
	 * @param ProductHelper     $product_helper
	 * @param ProductRepository $product_repository
	 */
	public function __construct( ShoppingContent $service, ProductHelper $product_helper, ProductRepository $product_repository ) {
		$this->service            = $service;
		$this->product_helper     = $product_helper;
		$this->product_repository = $product_repository;
	}

	/**
	 * Get product statuses.
	 *
	 * @return array List of product statuses.
	 * @throws GoogleException If the search call fails.
	 */
	public function get_product_statuses(): array {
		$sync_ready_products_ids = $this->product_repository->find_sync_ready_products()->get_product_ids();

		if ( count( $sync_ready_products_ids ) === 0 ) {
			return $this->calculate_statuses( [] );
		}

		$offer_ids = array_map(
			[ WCProductAdapter::class, 'get_offer_id' ],
			$sync_ready_products_ids
		);

		$next_page_token = null;
		$results         = [];

		do {

			$query = new MerchantProductViewReportQuery(
				[
					'ids'       => $offer_ids,
					'next_page' => $next_page_token,
				]
			);

			/** @var SearchResponse $response  */
			$response = $query
			->set_client( $this->service, $this->options->get_merchant_id() )
			->get_results();

			if ( $response->count() ) {
				$results = [ ...$results, ...$response->getResults() ];
			}

			$next_page_token = $response->getNextPageToken();

		} while ( $next_page_token );

		$statistics               = $this->calculate_statuses( $results );
		$statistics['not_synced'] = count( $sync_ready_products_ids ) - count( $results );
		return $statistics;
	}

	/**
	 * Calculate statistics for a list of report rows.
	 *
	 * @param ReportRow[] $rows List of report rows.
	 *
	 * @return array List of statistics.
	 */
	public function calculate_statuses( array $rows ): array {
		$statistics = [
			'active'              => 0,
			'not_synced'          => 0,
			MCStatus::EXPIRING    => 0,
			MCStatus::PENDING     => 0,
			MCStatus::DISAPPROVED => 0,
		];

		/** @var $row ReportRow  */
		foreach ( $rows as $row ) {
			/** @var ProductView $product_view  */
			$product_view    = $row->getProductView();
			$experation_date = $product_view->getExpirationDate();

			$formatted_expiration_date = DateTime::createFromFormat( 'Y-m-d', "{$experation_date->getYear()}-{$experation_date->getMonth()}-{$experation_date->getDay()}" );
			// Products are marked as expiring 3 days before the expiration date.
			// @see https://support.google.com/merchants/answer/160491?hl=en-IE#Expiring
			$formatted_expiration_date->modify( '-3 days' );
			if ( $formatted_expiration_date < new DateTime() ) {
				++$statistics[ MCStatus::EXPIRING ];
				continue;
			}

			switch ( $product_view->getAggregatedDestinationStatus() ) {
				case 'ELIGIBLE':
				case 'ELIGIBLE_LIMITED':
					++$statistics['active'];
					break;
				case 'PENDING':
					++$statistics[ MCStatus::PENDING ];
					break;
				case 'NOT_ELIGIBLE_OR_DISAPPROVED':
					++$statistics[ MCStatus::DISAPPROVED ];
					break;
				default:
					break;
			}
		}

		return $statistics;
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

			// Sort intervals to generate an ordered graph.
			if ( isset( $this->report_data['intervals'] ) ) {
				ksort( $this->report_data['intervals'] );
			}

			$this->remove_report_indexes( [ 'products', 'free_listings', 'intervals' ] );

			return $this->report_data;
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
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

			// Retrieve product title and add to report.
			if ( empty( $this->report_data['products'][ $product_id ]['name'] ) ) {
				$name = $this->product_helper->get_wc_product_title( (string) $product_id );
				$this->report_data['products'][ $product_id ]['name'] = $name;
			}
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
	 *
	 * @return array
	 */
	protected function get_report_row_metrics( ReportRow $row, array $args ): array {
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
