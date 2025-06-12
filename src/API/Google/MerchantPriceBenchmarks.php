<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceBenchmarksQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceSuggestionsQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceBenchmarksProductReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use DateTime;

/**
 * Class MerchantPriceBenchmarks
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantPriceBenchmarks implements OptionsAwareInterface {

	use OptionsAwareTrait;
	use ExceptionTrait;

	/**
	 * The shopping service.
	 *
	 * @var ShoppingContent
	 */
	protected $service;

	/**
	 * Merchant Report constructor.
	 *
	 * @param ShoppingContent $service
	 */
	public function __construct( ShoppingContent $service ) {
		$this->service = $service;
	}

	/**
	 * Get MerchantPriceBenchmarksQuery Query response.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array Associative array with price benchmarks data and the next page token.
	 *
	 * @throws ExceptionWithResponseData If the merchant price benchmarks data can't be retrieved.
	 */
	public function get_price_comparisons_data( array $args ): array {
		try {
			$response = ( new MerchantPriceBenchmarksQuery( $args ) )
			->set_client( $this->service, $this->options->get_merchant_id() )
			->get_results();

			$benchmark_data = $response->getResults() ?? [];

			if ( empty( $benchmark_data ) ) {
				return $benchmark_data;
			}

			// Map the benchmark data to a require format.
			$results = [];
			foreach ( $benchmark_data as $benchmark_result ) {
				$results[] = [
					'id'                            => $benchmark_result->getProductView()->getId(),
					'offer_id'                      => $benchmark_result->getProductView()->getOfferId(),
					'title'                         => $benchmark_result->getProductView()->getTitle(),
					'price_micros'                  => $benchmark_result->getProductView()->getPriceMicros(),
					'currency_code'                 => $benchmark_result->getProductView()->getCurrencyCode(),
					'country_code'                  => $benchmark_result->getPriceCompetitiveness()->getCountryCode(),
					'benchmark_price_micros'        => $benchmark_result->getPriceCompetitiveness()->getBenchmarkPriceMicros(),
					'benchmark_price_currency_code' => $benchmark_result->getPriceCompetitiveness()->getBenchmarkPriceCurrencyCode(),
				];
			}

			return $results;
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				__( 'Unable to retrieve price benchmark data', 'google-listings-and-ads' ),
				$e->getCode(),
				null,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Get MerchantPriceSuggestions Query response.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array Associative array with price benchmarks data and the next page token.
	 *
	 * @throws ExceptionWithResponseData If the merchant price suggestions data can't be retrieved.
	 */
	public function get_price_insights_data( array $args ): array {
		try {
			$response = ( new MerchantPriceSuggestionsQuery( $args ) )
			->set_client( $this->service, $this->options->get_merchant_id() )
			->get_results();

			$price_insights_data = $response->getResults() ?? [];

			if ( empty( $price_insights_data ) ) {
				return $price_insights_data;
			}

			// Map the benchmark data to a require format.
			$results = [];
			foreach ( $price_insights_data as $price_insights_result ) {
				$results[] = [
					'id'                                    => $price_insights_result->getProductView()->getId(),
					'offer_id'                              => $price_insights_result->getProductView()->getOfferId(),
					'title'                                 => $price_insights_result->getProductView()->getTitle(),
					'price_micros'                          => $price_insights_result->getProductView()->getPriceMicros(),
					'currency_code'                         => $price_insights_result->getProductView()->getCurrencyCode(),
					'suggested_price_micros'                => $price_insights_result->getPriceInsights()->getSuggestedPriceMicros(),
					'suggested_price_currency_code'         => $price_insights_result->getPriceInsights()->getSuggestedPriceCurrencyCode(),
					'predicted_impressions_change_fraction' => $price_insights_result->getPriceInsights()->getPredictedImpressionsChangeFraction(),
					'predicted_clicks_change_fraction'      => $price_insights_result->getPriceInsights()->getPredictedClicksChangeFraction(),
					'predicted_conversions_change_fraction' => $price_insights_result->getPriceInsights()->getPredictedConversionsChangeFraction(),

					/*
					 * The 'effectiveness' property wasn't added to the `PriceInsights` class until v0.354.0.
					 * Until we upgrade, we can use the magic getter to access the property directly from modelData.
					 * @see: https://github.com/googleapis/google-api-php-client-services/blob/v0.354.0/src/ShoppingContent/PriceInsights.php
					 */
					'effectiveness'                         => $price_insights_result->getPriceInsights()->effectiveness ?? 0,
				];
			}

			return $results;
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				__( 'Unable to retrieve price insights data', 'google-listings-and-ads' ),
				$e->getCode(),
				null,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Retrieves a specific product report based on the provided arguments.
	 *
	 * @param array $args An associative array of arguments used to filter or identify the product report.
	 *
	 * @return array The specific product report data as an associative array.
	 *
	 * @throws ExceptionWithResponseData If there is an error retrieving the product report.
	 */
	public function get_merchant_performance_data( array $args ): array {
		try {
			// Ensure we set the default date range for the query if not provided.
			$args = wp_parse_args(
				$args,
				$this->get_default_between_dates()
			);

			$response = ( new MerchantPriceBenchmarksProductReportQuery( $args ) )
			->set_client( $this->service, $this->options->get_merchant_id() )
			->get_results();

			$performance_data = $response->getResults() ?? [];

			if ( empty( $performance_data ) ) {
				return $performance_data;
			}

			// Map the performance data to a require format.
			$results = [];
			foreach ( $performance_data as $performance_result ) {
				$results[] = [
					'offer_id'    => $performance_result->getSegments()->getOfferId(),
					'clicks'      => $performance_result->getMetrics()->getClicks(),
					'impressions' => $performance_result->getMetrics()->getImpressions(),
					'ctr'         => $performance_result->getMetrics()->getCtr(),
					'conversions' => $performance_result->getMetrics()->getConversions(),
				];
			}

			return $results;
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_mc_client_exception', $e, __METHOD__ );
			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				__( 'Unable to retrieve product metrics data', 'google-listings-and-ads' ),
				$e->getCode(),
				null,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Get start and end dates for the previous week from Monday to Sunday.
	 *
	 * @return array An associative array of price benchmark data.
	 */
	protected function get_default_between_dates(): array {
		$today       = new DateTime( 'today' );
		$day_of_week = (int) $today->format( 'N' ); // 1 (Monday) to 7 (Sunday).

		// Calculate the start and end dates for the last Monday to Sunday period.
		$end_date   = ( clone $today )->modify( '-' . $day_of_week . ' days' ); // Last Sunday
		$start_date = ( clone $end_date )->modify( '-6 days' ); // Last Monday

		return [
			'after'  => $start_date->format( 'Y-m-d' ),
			'before' => $end_date->format( 'Y-m-d' ),
		];
	}
}
