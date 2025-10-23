<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsRecommendationsQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Google\Ads\GoogleAds\V20\Resources\Recommendation;
use Google\Ads\GoogleAds\V20\Enums\RecommendationTypeEnum\RecommendationType;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsRecommendationsService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AdsRecommendationsService implements ContainerAwareInterface, OptionsAwareInterface, TransientsAwareInterface, Service {

	use ContainerAwareTrait;
	use OptionsAwareTrait;
	use TransientsAwareTrait;
	use MicroTrait;

	/**
	 * Allowed recommendation types.
	 */
	public const VALID_RECOMMENDATION_TYPES = [
		'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		'CAMPAIGN_BUDGET',
		'MARGINAL_ROI_CAMPAIGN_BUDGET',
	];

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * Ads constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Retrieves recommendations from the database for the specified type and campaign ID.
	 *
	 * @param array $args {
	 *     Optional. Arguments to filter recommendations.
	 *
	 *     @type array $types       Type of recommendations to retrieve.
	 *     @type int   $campaign_id Campaign ID to filter recommendations.
	 * }
	 * @return array Array of recommendations.
	 */
	public function get_recommendations( array $args = [] ): array {
		// Make sure a valid type is passed.
		$types = isset( $args['types'] ) && is_array( $args['types'] ) ? self::get_valid_recommendation_types( $args['types'] ) : [];

		if ( empty( $types ) ) {
			return [];
		}

		$transient = $this->transients->get( TransientsInterface::ADS_RECOMMENDATIONS );
		$cache_key = md5( wp_json_encode( $args ) );

		if ( $transient && ! empty( $transient[ $cache_key ] ) ) {
			return $transient[ $cache_key ];
		}

		try {
			$result = $this->get_google_recommendations( $args );
		} catch ( \Exception $e ) {
			do_action( 'woocommerce_gla_debug_message', $e->getMessage(), __METHOD__ );

			return [];
		}

		$recommendations = [];

		foreach ( $result as $item ) {
			$recommendations[] = [
				'id'              => (int) ( $item['recommendation_id'] ?? 0 ),
				'type'            => $item['recommendation_type'] ?? '',
				'resource_name'   => $item['recommendation_resource_name'] ?? '',
				'campaign_id'     => (int) ( $item['recommendation_campaign_id'] ?? 0 ),
				'campaign_name'   => $item['recommendation_campaign_name'] ?? '',
				'campaign_status' => $item['recommendation_campaign_status'] ?? '',
				'customer_id'     => (int) ( $item['recommendation_customer_id'] ?? 0 ),
				'details'         => $item['recommendation_details'] ?? [],
				'last_synced'     => isset( $item['recommendation_last_synced'] )
					? gmdate( 'c', strtotime( $item['recommendation_last_synced'] ) )
					: null,
			];
		}

		$transient[ $cache_key ] = $recommendations;
		$this->transients->set( TransientsInterface::ADS_RECOMMENDATIONS, $transient, HOUR_IN_SECONDS * 12 );

		return $recommendations;
	}

	/**
	 * Filters the provided recommendation types to only include valid types.
	 *
	 * @param array $types Array of recommendation types to filter.
	 * @return array Filtered array containing only valid recommendation types.
	 */
	public static function get_valid_recommendation_types( array $types ): array {
		if ( empty( $types ) ) {
			return [];
		}

		return array_intersect( $types, self::VALID_RECOMMENDATION_TYPES );
	}

	/**
	 * Returns additional columns by the recommendation type.
	 *
	 * @param array $types The recommendation types.
	 * @return array Additional columns for that recommendation type.
	 */
	private function get_additional_columns_for_recommendation_type( array $types ): array {
		$columns = [
			'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH' => [],
			'CAMPAIGN_BUDGET'                     => [
				'recommendation_campaign_budget_recommendation' => 'recommendation.campaign_budget_recommendation',
			],
			'MARGINAL_ROI_CAMPAIGN_BUDGET'        => [
				'recommendation_marginal_roi_campaign_budget_recommendation' => 'recommendation.marginal_roi_campaign_budget_recommendation',
			],
		];

		$add = [];

		foreach ( $types as $type ) {
			if ( ! isset( $columns[ $type ] ) ) {
				continue;
			}

			$add = array_merge( $add, $columns[ $type ] );
		}

		return $add;
	}

	/**
	 * Retrieves recommendations from the Google Ads API.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of recommendations.
	 *
	 * @throws Exception If the recommendations data can't be retrieved.
	 */
	public function get_google_recommendations( $args ): array {
		// Return early if no connected ads account.
		$ads_id = $this->options->get_ads_id();

		if ( empty( $ads_id ) ) {
			return [];
		}

		try {
			$query = ( new AdsRecommendationsQuery() )
			->set_client( $this->client, $ads_id );

			$types       = isset( $args['types'] ) && is_array( $args['types'] ) ? self::get_valid_recommendation_types( $args['types'] ) : [];
			$campaign_id = isset( $args['campaign_id'] ) ? (int) $args['campaign_id'] : 0;

			if ( empty( $types ) ) {
				return [];
			}

			$query->where( 'recommendation.type', $types, 'IN' );

			if ( ! empty( $campaign_id ) ) {
				$query->where( 'campaign.id', $campaign_id );
			}

			// Add additional columns based on the recommendation type.
			$columns = $this->get_additional_columns_for_recommendation_type( $types );

			if ( ! empty( $columns ) ) {
				$query->add_columns( $columns );
			}

			$response    = $query->get_results();
			$result      = [];
			$last_synced = gmdate( 'Y-m-d H:i:s' );

			foreach ( $response->iterateAllElements() as $row ) {
				if ( ! $row->hasRecommendation() ) {
					continue;
				}

				$recommendation = $row->getRecommendation();

				// Skip if recommendation is not valid.
				if ( ! $recommendation instanceof Recommendation ) {
					continue;
				}

				$campaign = $row->getCampaign();
				$customer = $row->getCustomer();

				$recommendation_type    = RecommendationType::name( $recommendation->getType() );
				$recommendation_details = [];

				// Add recommendation details depending on the type.
				if ( in_array( $recommendation_type, [ 'CAMPAIGN_BUDGET', 'MARGINAL_ROI_CAMPAIGN_BUDGET' ], true ) ) {
					$budget_recommendation = $recommendation->getCampaignBudgetRecommendation();
					$budget_options        = [];

					// If MARGINAL_ROI_CAMPAIGN_BUDGET type get the correct budget recommendation.
					if ( $recommendation->hasMarginalRoiCampaignBudgetRecommendation() ) {
						$budget_recommendation = $recommendation->getMarginalRoiCampaignBudgetRecommendation();
					}

					if ( ! $budget_recommendation ) {
						continue;
					}

					$current_budget     = $budget_recommendation->getCurrentBudgetAmountMicros();
					$recommended_budget = $budget_recommendation->getRecommendedBudgetAmountMicros();

					// Skip recommendation if the recommended budget is lower than the current budget.
					if ( $recommended_budget <= $current_budget ) {
						continue;
					}

					foreach ( $budget_recommendation->getBudgetOptions() as $option ) {
						$impact        = $option->getImpact();
						$potential     = $impact->getPotentialMetrics();
						$budget_amount = $option->getBudgetAmountMicros();

						// Determine budget option level.
						$level = __( 'Low', 'google-listings-and-ads' );

						if ( $budget_amount === $current_budget ) {
							$level = __( 'Current', 'google-listings-and-ads' );
						} elseif ( $budget_amount === $recommended_budget ) {
							$level = __( 'Recommended', 'google-listings-and-ads' );
						} elseif ( $budget_amount > $recommended_budget ) {
							$level = __( 'High', 'google-listings-and-ads' );
						}

						$budget_options[] = [
							'budget_amount' => $this->from_micro( $budget_amount ),
							'level'         => $level,
							'metrics'       => [
								'cost'              => $this->from_micro( $potential->getCostMicros() ),
								'conversions'       => $potential->getConversions(),
								'conversions_value' => $potential->getConversionsValue(),
							],
						];
					}

					$recommendation_details = [
						'campaign_budget_recommendation' => [
							'current_budget_amount'     => $this->from_micro( $current_budget ),
							'recommended_budget_amount' => $this->from_micro( $recommended_budget ),
							'budget_options'            => $budget_options,
						],
					];
				}

				$recommendation_resource_name = $recommendation->getResourceName();
				if ( empty( $recommendation_resource_name ) ) {
					continue;
				}

				$resource_name     = explode( '/', $recommendation_resource_name );
				$recommendation_id = (int) end( $resource_name );

				$result[] = [
					'recommendation_id'              => $recommendation_id,
					/**
					 * Note: The 'id' field below refers to the Recommendation resource's ID property, not the field name itself.
					 * Reference: https://github.com/googleads/google-ads-php/blob/main/src/Google/Ads/GoogleAds/V20/Resources/Recommendation.php#L25-L30
					 *
					 * We use the static name for the recommendation type instead of `$recommendation->getType()`
					 * to ensure consistency and avoid potential issues with dynamic values or API changes.
					 */
					'recommendation_type'            => $recommendation_type,
					'recommendation_resource_name'   => $recommendation_resource_name,
					'recommendation_campaign_id'     => $campaign->getId(),
					'recommendation_campaign_name'   => $campaign->getName(),
					'recommendation_campaign_status' => $campaign->getStatus(),
					'recommendation_customer_id'     => $customer->getId(),
					'recommendation_details'         => $recommendation_details,
					'recommendation_last_synced'     => $last_synced,
				];
			}

			return $result;
		} catch ( GoogleException $e ) {
			throw new Exception( __( 'Unable to retrieve Google Ads recommendations.', 'google-listings-and-ads' ) . $e->getMessage(), $e->getCode() );
		}
	}
}
