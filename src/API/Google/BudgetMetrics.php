<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\LocationIDTrait;
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
use Google\Ads\GoogleAds\V20\Services\GenerateRecommendationsRequest\BudgetInfo;
use Google\ApiCore\ApiException;

/**
 * Class BudgetMetrics
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class BudgetMetrics implements OptionsAwareInterface, TransientsAwareInterface {

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
	 * BudgetMetrics constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Fetch budget metrics from Google Ads API.
	 *
	 * @param float $budget        Budget to fetch metrics for.
	 * @param array $country_codes List of countries to include.
	 *
	 * @return array|null List of metrics.
	 */
	public function get_metrics( float $budget, array $country_codes ): ?array {
		$cache_key = strtolower( join( '-', $country_codes ) . '-' . $budget );
		$transient = $this->transients->get( TransientsInterface::ADS_BUDGET_METRICS );

		// Check if we have the budget metrics cached in the transient.
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
				'budget_info'                => new BudgetInfo(
					[
						'current_budget' => $this->to_micro( $budget ),
					],
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

				// Parse the metrics for the given budget.
				$metrics = $this->parse_metrics( $campaign_budget_recommendation, $budget );

				// Merge with previously cached metrics.
				if ( ! is_array( $transient ) ) {
					$transient = [];
				}
				$transient[ $cache_key ] = $metrics;
				$this->transients->set( TransientsInterface::ADS_BUDGET_METRICS, $transient, HOUR_IN_SECONDS * 12 );

				return $metrics;
			}
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return null;
	}

	/**
	 * Parse metrics for the given budget.
	 *
	 * @param CampaignBudgetRecommendation $recommendation Collection of recommendation options.
	 * @param float                        $budget         Budget to fetch metrics for.
	 *
	 * @return array|null Metrics for the given budget.
	 */
	protected function parse_metrics( CampaignBudgetRecommendation $recommendation, float $budget ): ?array {
		foreach ( $recommendation->getBudgetOptions() as $budget_option ) {
			$amount = $this->from_micro( $budget_option->getBudgetAmountMicros() );
			if ( abs( $amount - $budget ) < 0.00001 ) {
				$metrics = $budget_option->getImpact()->getPotentialMetrics();

				return [
					'cost'              => $this->from_micro( $metrics->getCostMicros() ),
					'conversions'       => $metrics->getConversions(),
					'conversions_value' => $metrics->getConversionsValue(),
				];
			}
		}

		return null;
	}
}
