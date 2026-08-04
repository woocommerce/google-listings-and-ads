<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceBenchmarksQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceSuggestionsQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantPriceBenchmarksProductReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use DateTime;

/**
 * Class MerchantPriceBenchmarks
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantPriceBenchmarks implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Merchant API client.
	 *
	 * @var MerchantApiClient
	 */
	protected $client;

	/**
	 * MerchantPriceBenchmarks constructor.
	 *
	 * @param MerchantApiClient $client
	 */
	public function __construct( MerchantApiClient $client ) {
		$this->client = $client;
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
			->set_client( $this->client, $this->options->get_merchant_id() )
			->get_results();

			$results = [];
			foreach ( $response['results'] ?? [] as $row ) {
				$view = $row['priceCompetitivenessProductView'] ?? [];

				$results[] = [
					'id'                            => $view['id'] ?? '',
					'offer_id'                      => $view['offerId'] ?? '',
					'title'                         => $view['title'] ?? '',
					'price_micros'                  => $view['price']['amountMicros'] ?? '',
					'currency_code'                 => $view['price']['currencyCode'] ?? '',
					'country_code'                  => $view['reportCountryCode'] ?? '',
					'benchmark_price_micros'        => $view['benchmarkPrice']['amountMicros'] ?? '',
					'benchmark_price_currency_code' => $view['benchmarkPrice']['currencyCode'] ?? '',
				];
			}

			return $results;
		} catch ( MerchantApiException $e ) {
			throw new ExceptionWithResponseData(
				__( 'Unable to retrieve price benchmark data', 'google-listings-and-ads' ),
				$e->get_http_status(),
				null,
				[ 'errors' => $e->get_errors() ]
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
			->set_client( $this->client, $this->options->get_merchant_id() )
			->get_results();

			$results = [];
			foreach ( $response['results'] ?? [] as $row ) {
				$view = $row['priceInsightsProductView'] ?? [];

				$results[] = [
					'id'                                    => $view['id'] ?? '',
					'offer_id'                              => $view['offerId'] ?? '',
					'title'                                 => $view['title'] ?? '',
					'price_micros'                          => $view['price']['amountMicros'] ?? '',
					'currency_code'                         => $view['price']['currencyCode'] ?? '',
					'suggested_price_micros'                => $view['suggestedPrice']['amountMicros'] ?? '',
					'suggested_price_currency_code'         => $view['suggestedPrice']['currencyCode'] ?? '',
					'predicted_impressions_change_fraction' => $view['predictedImpressionsChangeFraction'] ?? 0,
					'predicted_clicks_change_fraction'      => $view['predictedClicksChangeFraction'] ?? 0,
					'predicted_conversions_change_fraction' => $view['predictedConversionsChangeFraction'] ?? 0,
					'effectiveness'                         => $view['effectiveness'] ?? 0,
				];
			}

			return $results;
		} catch ( MerchantApiException $e ) {
			throw new ExceptionWithResponseData(
				__( 'Unable to retrieve price insights data', 'google-listings-and-ads' ),
				$e->get_http_status(),
				null,
				[ 'errors' => $e->get_errors() ]
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
			->set_client( $this->client, $this->options->get_merchant_id() )
			->get_results();

			$results = [];
			foreach ( $response['results'] ?? [] as $row ) {
				$view = $row['productPerformanceView'] ?? [];

				$results[] = [
					'offer_id'    => $view['offerId'] ?? '',
					'clicks'      => $view['clicks'] ?? 0,
					'impressions' => $view['impressions'] ?? 0,
					'ctr'         => $view['clickThroughRate'] ?? 0,
					'conversions' => $view['conversions'] ?? 0,
				];
			}

			return $results;
		} catch ( MerchantApiException $e ) {
			throw new ExceptionWithResponseData(
				__( 'Unable to retrieve product metrics data', 'google-listings-and-ads' ),
				$e->get_http_status(),
				null,
				[ 'errors' => $e->get_errors() ]
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
