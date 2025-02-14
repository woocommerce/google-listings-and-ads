<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Google\Ads\GoogleAds\V18\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V18\Enums\BiddingStrategyTypeEnum\BiddingStrategyType;
use Google\Ads\GoogleAds\V18\Enums\RecommendationTypeEnum\RecommendationType;
use Google\Ads\GoogleAds\V18\Resources\Recommendation\CampaignBudgetRecommendation;
use Google\Ads\GoogleAds\V18\Services\GenerateRecommendationsRequest;
use Google\Ads\GoogleAds\V18\Services\GenerateRecommendationsRequest\AssetGroupInfo;
use Google\Ads\GoogleAds\V18\Services\GenerateRecommendationsRequest\BiddingInfo;
use Google\ApiCore\ApiException;

/**
 * Class BudgetRecommendations
 * https://developers.google.com/google-ads/api/docs/performance-max/overview
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class BudgetRecommendations implements OptionsAwareInterface {

	use MicroTrait;
	use OptionsAwareTrait;
	use PluginHelper;

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
	 *
	 * @param array $country_codes List of countries to include.
	 *
	 * @return array|null Recommendations, including metrics.
	 */
	public function get_recommendations( array $country_codes ): ?array {
		/*
			TODO: Location ID's need to be fetched with a query:
			SELECT
				geo_target_constant.canonical_name,
				geo_target_constant.id,
				geo_target_constant.name,
				geo_target_constant.country_code,
				geo_target_constant.target_type
			FROM geo_target_constant
			WHERE geo_target_constant.country_code IN "US,CA"
			AND geo_target_constant.target_type = "Country"
		*/

		$request = new GenerateRecommendationsRequest(
			[
				'customer_id'              => $this->options->get_ads_id(),
				'recommendation_types'     => [ RecommendationType::CAMPAIGN_BUDGET ],
				'advertising_channel_type' => AdvertisingChannelType::PERFORMANCE_MAX,
				// TODO: add 'positive_locations_ids'   => '',
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
			]
		);

		try {
			$response = $this->client->getRecommendationServiceClient()->generateRecommendations( $request );

			foreach ( $response->getRecommendations() as $recommendation ) {
				$campaign_budget_recommendation = $recommendation->getCampaignBudgetRecommendation();
				if ( ! $campaign_budget_recommendation ) {
					continue;
				}

				// Return recommended budget for the first country.
				$recommended = $this->select_recommended_budget( $campaign_budget_recommendation );
				if ( $recommended ) {
					$recommended['country'] = reset( $country_codes );
					return $recommended;
				}
			}
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return null;
	}

	/**
	 * Select the suggested budget recommendation and return metrics.
	 *
	 * @param CampaignBudgetRecommendation $recommendation
	 *
	 * @return array|null Recommendations, including metrics.
	 */
	protected function select_recommended_budget( CampaignBudgetRecommendation $recommendation ): ?array {
		// Map all available budget options.
		$options = [];
		foreach ( $recommendation->getBudgetOptions() as $budget_option ) {
			$amount  = $this->from_micro( $budget_option->getBudgetAmountMicros() );
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

		return $options[ (string) $closest ] ?: null;
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
