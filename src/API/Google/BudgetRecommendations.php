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
use Google\Ads\GoogleAds\V20\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V20\Enums\BiddingStrategyTypeEnum\BiddingStrategyType;
use Google\Ads\GoogleAds\V20\Enums\RecommendationTypeEnum\RecommendationType;
use Google\Ads\GoogleAds\V20\Resources\Recommendation\CampaignBudgetRecommendation;
use Google\Ads\GoogleAds\V20\Services\GenerateRecommendationsRequest;
use Google\Ads\GoogleAds\V20\Services\GenerateRecommendationsRequest\AssetGroupInfo;
use Google\Ads\GoogleAds\V20\Services\GenerateRecommendationsRequest\BiddingInfo;
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
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * BudgetRecommendations constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Fetch budget recommendations (with metrics) from Google Ads API.
	 * This function will only return a single recommendation in the list, with the first country code.
	 *
	 * @param array $country_codes List of countries to include.
	 *
	 * @return array|null List of recommendations (including metrics).
	 */
	public function get_recommendations( array $country_codes ): ?array {
		$cache_key = strtolower( join( '-', $country_codes ) );
		$transient = $this->transients->get( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS );

		// Check if we have the budget recommendations cached in the transient.
		if ( $transient && ! empty( $transient[ $cache_key ] ) ) {
			return $transient[ $cache_key ];
		}

		$location_ids = $this->get_location_ids( $country_codes );

		$request = new GenerateRecommendationsRequest(
			[
				'customer_id'                => $this->options->get_ads_id(),
				'merchant_center_account_id' => $this->options->get_merchant_id(),
				'recommendation_types'       => [ RecommendationType::CAMPAIGN_BUDGET ],
				'advertising_channel_type'   => AdvertisingChannelType::PERFORMANCE_MAX,
				'positive_locations_ids'     => array_keys( $location_ids ),
				'country_codes'              => $country_codes,
				'bidding_info'               => new BiddingInfo(
					[
						'bidding_strategy_type' => BiddingStrategyType::MAXIMIZE_CONVERSION_VALUE,
					]
				),
				'asset_group_info'           => [
					new AssetGroupInfo(
						[
							'final_url' => $this->get_site_url(),
						],
					),
				],
			]
		);

		try {
			$response = $this->client->getRecommendationServiceClient()->generateRecommendations( $request );

			foreach ( $response->getRecommendations() as $recommendation ) {
				$campaign_budget_recommendation = $recommendation->getCampaignBudgetRecommendation();
				if ( ! $campaign_budget_recommendation ) {
					continue;
				}

				// Parse all the returned recommendations and assign to the first country.
				$recommendations = $this->parse_recommendations( $campaign_budget_recommendation, reset( $country_codes ) );
				$this->transients->set( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS, [ $cache_key => $recommendations ], HOUR_IN_SECONDS * 12 );
				return $recommendations;
			}
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return null;
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
