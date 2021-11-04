<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantFreeListingReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use DateTime;
use Exception;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\SearchResponse;

/**
 * Class MerchantMetrics
 *
 * @since   x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantMetrics implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingContent
	 */
	protected $service;

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * @var TransientsInterface
	 */
	protected $transients;

	protected const MAX_QUERY_START_DATE = '2020-01-01';

	/**
	 * MerchantMetrics constructor.
	 *
	 * @param ShoppingContent     $service
	 * @param WP                  $wp
	 * @param TransientsInterface $transients
	 */
	public function __construct( ShoppingContent $service, WP $wp, TransientsInterface $transients ) {
		$this->service    = $service;
		$this->wp         = $wp;
		$this->transients = $transients;
	}

	/**
	 * Get free listing metrics.
	 *
	 * @return array Of metrics or empty if no metrics were available.
	 *      @type int $clicks Number of free clicks.
	 *      @type int $impressions NUmber of free impressions.
	 *
	 * @throws Exception When unable to get clicks data.
	 */
	public function get_free_listing_metrics(): array {
		if ( ! $this->options->get_merchant_id() ) {
			// Merchant account not set up
			return [];
		}

		// Google API requires a date clause to be set but there doesn't seem to be any limits on how wide the range
		$query = ( new MerchantFreeListingReportQuery( [] ) )
			->set_client( $this->service, $this->options->get_merchant_id() )
			->where_date_between( self::MAX_QUERY_START_DATE, $this->get_tomorrow() )
			->fields( [ 'clicks', 'impressions' ] );

		/** @var SearchResponse $response */
		$response = $query->get_results();

		if ( empty( $response ) || empty( $response->getResults() ) ) {
			return [];
		}

		$report_row = $response->getResults()[0];

		return [
			'clicks'      => (int) $report_row->getMetrics()->getClicks(),
			'impressions' => (int) $report_row->getMetrics()->getImpressions(),
		];
	}

	/**
	 * Get free listing metrics but cached for 12 hours.
	 *
	 * PLEASE NOTE: These metrics will not be 100% accurate since there is no invalidation apart from the 12 hour refresh.
	 *
	 * @return array Of metrics or empty if no metrics were available.
	 *      @type int $clicks Number of free clicks.
	 *      @type int $impressions NUmber of free impressions.
	 *
	 * @throws Exception When unable to get data.
	 */
	public function get_cached_free_listing_metrics(): array {
		$value = $this->transients->get( TransientsInterface::FREE_LISTING_METRICS );

		if ( $value === null ) {
			$value = $this->get_free_listing_metrics();
			$this->transients->set( TransientsInterface::FREE_LISTING_METRICS, $value, HOUR_IN_SECONDS * 12 );
		}

		return $value;
	}

	/**
	 * Get tomorrow's date to ensure we include any metrics from the current day.
	 *
	 * @return string
	 */
	protected function get_tomorrow(): string {
		return ( new DateTime( 'tomorrow', $this->wp->wp_timezone() ) )->format( 'Y-m-d' );
	}

}
