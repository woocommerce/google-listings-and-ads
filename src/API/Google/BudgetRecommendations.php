<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\LocationIDTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Google\Ads\GoogleAds\V23\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V23\Enums\BiddingStrategyTypeEnum\BiddingStrategyType;
use Google\Ads\GoogleAds\V23\Enums\RecommendationTypeEnum\RecommendationType;
use Google\Ads\GoogleAds\V23\Resources\Recommendation\CampaignBudgetRecommendation;
use Google\Ads\GoogleAds\V23\Services\GenerateRecommendationsRequest;
use Google\Ads\GoogleAds\V23\Services\GenerateRecommendationsRequest\AssetGroupInfo;
use Google\Ads\GoogleAds\V23\Services\GenerateRecommendationsRequest\BiddingInfo;
use Google\ApiCore\ApiException;

/**
 * Class BudgetRecommendations
 * https://developers.google.com/google-ads/api/rest/reference/rest/v20/Recommendation#CampaignBudgetRecommendation
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class BudgetRecommendations implements OptionsAwareInterface, TransientsAwareInterface {

	use MicroTrait;
	use OptionsAwareTrait;
	use PluginHelper;
	use TransientsAwareTrait;
	use LocationIDTrait;

	/**
	 * How long a saved recommendation stays valid, in seconds.
	 *
	 * @var int
	 */
	protected const CACHE_LIFETIME = 12 * HOUR_IN_SECONDS;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * @var MerchantMetrics
	 */
	protected $merchant_metrics;

	/**
	 * BudgetRecommendations constructor.
	 *
	 * @param GoogleAdsClient $client
	 * @param MerchantMetrics $merchant_metrics
	 */
	public function __construct( GoogleAdsClient $client, MerchantMetrics $merchant_metrics ) {
		$this->client           = $client;
		$this->merchant_metrics = $merchant_metrics;
	}

	/**
	 * Fetch budget recommendations (with metrics) from Google Ads API.
	 * This function will only return a single recommendation in the list, with the first country code.
	 *
	 * @param array  $country_codes      List of countries to include.
	 * @param string $advertising_source One of the `AdvertisingSource` values.
	 *
	 * @return array|null List of recommendations (including metrics).
	 */
	public function get_recommendations( array $country_codes, string $advertising_source = AdvertisingSource::PRODUCT ): ?array {
		$is_new_customer = 0 === $this->merchant_metrics->get_campaign_count();
		$is_page         = AdvertisingSource::PAGE === $advertising_source;
		$cache_key       = strtolower( join( '-', $country_codes ) ) . ( $is_new_customer ? '-new' : '-existing' ) . '-' . $advertising_source;
		$transient       = $this->transients->get( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS );

		// Check if we have the budget recommendations cached in the transient.
		$cached = $this->get_cached_recommendations( $transient, $cache_key );
		if ( $cached ) {
			return $cached;
		}

		$location_ids = $this->get_location_ids( $country_codes );
		$merchant_id  = $this->options->get_merchant_id();

		$request_data = [
			'customer_id'              => $this->options->get_ads_id(),
			'recommendation_types'     => [ RecommendationType::CAMPAIGN_BUDGET ],
			'advertising_channel_type' => AdvertisingChannelType::PERFORMANCE_MAX,
			'positive_locations_ids'   => array_keys( $location_ids ),
			'country_codes'            => $country_codes,
			'is_new_customer'          => $is_new_customer,
			'bidding_info'             => new BiddingInfo(
				[
					'bidding_strategy_type' => BiddingStrategyType::MAXIMIZE_CONVERSION_VALUE,
				]
			),
			'asset_group_info'         => [
				new AssetGroupInfo(
					[
						'final_url' => $this->get_site_url(),
					],
				),
			],
		];

		// Only include merchant_center_account_id if Merchant Center account is connected and the campaign uses the product feed.
		if ( $merchant_id > 0 && ! $is_page ) {
			$request_data['merchant_center_account_id'] = $merchant_id;
		}

		$request = new GenerateRecommendationsRequest( $request_data );

		try {
			$response = $this->client->getRecommendationServiceClient()->generateRecommendations( $request );

			foreach ( $response->getRecommendations() as $recommendation ) {
				$campaign_budget_recommendation = $recommendation->getCampaignBudgetRecommendation();
				if ( ! $campaign_budget_recommendation ) {
					continue;
				}

				// Parse all the returned recommendations and assign to the first country.
				$recommendations = $this->parse_recommendations( $campaign_budget_recommendation, reset( $country_codes ) );

				// Merge with the recommendations that are still valid.
				$transient               = $this->remove_expired_recommendations( $transient );
				$transient[ $cache_key ] = [
					'saved_at'        => time(),
					'recommendations' => $recommendations,
				];
				$this->transients->set( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS, $transient, self::CACHE_LIFETIME );
				return $recommendations;
			}
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return null;
	}

	/**
	 * Get the saved recommendations for a cache key, unless they have expired.
	 *
	 * An entry saved before the save time was recorded is a plain list of recommendations and is still returned.
	 *
	 * @param mixed  $transient The value of the budget recommendations transient.
	 * @param string $cache_key The cache key to look up.
	 *
	 * @return array|null
	 */
	protected function get_cached_recommendations( $transient, string $cache_key ): ?array {
		$entry = is_array( $transient ) ? ( $transient[ $cache_key ] ?? null ) : null;

		if ( empty( $entry ) ) {
			return null;
		}

		if ( ! isset( $entry['saved_at'] ) ) {
			return $entry;
		}

		return time() - $entry['saved_at'] < self::CACHE_LIFETIME ? $entry['recommendations'] : null;
	}

	/**
	 * Keep only the saved recommendations that have not expired, so saving a new entry doesn't keep an old one alive.
	 *
	 * @param mixed $transient The value of the budget recommendations transient.
	 *
	 * @return array
	 */
	protected function remove_expired_recommendations( $transient ): array {
		if ( ! is_array( $transient ) ) {
			return [];
		}

		return array_filter(
			$transient,
			function ( $entry ) {
				return isset( $entry['saved_at'] ) && time() - $entry['saved_at'] < self::CACHE_LIFETIME;
			}
		);
	}

	/**
	 * Return all budget recommendations including metrics.
	 *
	 * @param CampaignBudgetRecommendation $recommendation  Collection of recommendation options.
	 * @param string                       $country_code    Primary country code.
	 *
	 * @return array|null Recommendations (including metrics).
	 */
	protected function parse_recommendations( CampaignBudgetRecommendation $recommendation, string $country_code ): ?array {
		// Map all available budget options.
		$options = [];
		foreach ( $recommendation->getBudgetOptions() as $budget_option ) {
			$amount = $this->from_micro( $budget_option->getBudgetAmountMicros() );
			if ( ! $amount ) {
				continue;
			}

			$metrics = $budget_option->getImpact()->getPotentialMetrics();

			$options[ (string) $amount ] = [
				'daily_budget' => $amount,
				'metrics'      => [
					'cost'              => $this->from_micro( $metrics->getCostMicros() ),
					'conversions'       => $metrics->getConversions(),
					'conversions_value' => $metrics->getConversionsValue(),
				],
			];
		}

		// Find closest match based on recommended amount.
		$numbers = array_map( 'floatval', array_keys( $options ) );
		$closest = $this->find_closest( $this->from_micro( $recommendation->getRecommendedBudgetAmountMicros() ), $numbers );

		// Add each option and assign it's level
		$recommendations = [];
		foreach ( $options as $option ) {
			if ( $option['daily_budget'] === $closest ) {
				$level = __( 'Recommended', 'google-listings-and-ads' );
				$index = 0;
			} elseif ( $option['daily_budget'] > $closest ) {
				$level = __( 'High', 'google-listings-and-ads' );
				$index = 1;
			} else {
				$level = __( 'Low', 'google-listings-and-ads' );
				$index = 2;
			}

			$recommendations[ $index ] = array_merge(
				$option,
				[
					'country' => $country_code,
					'level'   => $level,
				]
			);
		}

		if ( empty( $recommendations ) ) {
			return null;
		}

		// Sort recommendations in the order: recommended, high, low.
		ksort( $recommendations );
		return $recommendations;
	}

	/**
	 * Find closest matching number in an array of numbers.
	 *
	 * @param float $number  Number to search for.
	 * @param array $numbers List of numbers to search in.
	 *
	 * @return float|null Closest number found.
	 */
	protected function find_closest( float $number, array $numbers ): ?float {
		if ( empty( $numbers ) ) {
			return null;
		}

		usort(
			$numbers,
			function ( $a, $b ) use ( $number ) {
				return abs( $number - (float) $a ) <=> abs( $number - (float) $b );
			}
		);

		return reset( $numbers );
	}
}
