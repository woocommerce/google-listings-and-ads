<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AdsRecommendationsQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsRecommendationsQuery as GoogleAdsRecommendationsQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Google\Ads\GoogleAds\V20\Resources\Recommendation;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsRecommendationsService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AdsRecommendationsService implements ContainerAwareInterface, OptionsAwareInterface, Service {

	use ContainerAwareTrait;
	use OptionsAwareTrait;

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
	 *     @type array $type        Type of recommendations to retrieve.
	 *     @type int   $campaign_id Campaign ID to filter recommendations.
	 * }
	 * @return array Array of recommendations.
	 */
	public function get_recommendations( array $args = [] ): array {
		/** @var AdsRecommendationsQuery $query */
		$query = $this->container->get( AdsRecommendationsQuery::class );

		$type        = isset( $args['type'] ) && is_array( $args['type'] ) ? get_valid_recommendation_types( $args['type'] ) : [];
		$campaign_id = isset( $args['campaign_id'] ) ? (int) $args['campaign_id'] : 0;

		if ( empty( $type ) ) {
			return [];
		}

		$query->where( 'recommendation_type', $type, 'IN' );

		if ( $campaign_id ) {
			$query->where( 'recommendation_campaign_id', $campaign_id );
		} else {
			// Only return recommendations for the highest spend campaign.
			$ads_campaign = $this->container->get( AdsCampaign::class );
			$campaign     = $ads_campaign->get_highest_spend_campaign();

			if ( ! empty( $campaign ) ) {
				$query->where( 'recommendation_campaign_id', $campaign['id'] );
			}
		}

		$result = $query->get_results();

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
				'last_synced'     => isset( $item['recommendation_last_synced'] )
					? gmdate( 'c', strtotime( $item['recommendation_last_synced'] ) )
					: null,
			];
		}

		return $recommendations;
	}

	/**
	 * Filters the provided recommendation types to only include valid types.
	 *
	 * @param array $type Array of recommendation types to filter.
	 * @return array Filtered array containing only valid recommendation types.
	 */
	public static function get_valid_recommendation_types( array $type ): array {
		if ( empty( $type ) ) {
			return [];
		}

		return array_intersect( $type, self::VALID_RECOMMENDATION_TYPES );
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
		try {
			$response = ( new GoogleAdsRecommendationsQuery( $args ) )
			->set_client( $this->client, $this->options->get_ads_id() )
			->get_results();

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
					'recommendation_type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
					'recommendation_resource_name'   => $recommendation_resource_name,
					'recommendation_campaign_id'     => $campaign->getId(),
					'recommendation_campaign_name'   => $campaign->getName(),
					'recommendation_campaign_status' => $campaign->getStatus(),
					'recommendation_customer_id'     => $customer->getId(),
					'recommendation_last_synced'     => $last_synced,
				];
			}

			return $result;
		} catch ( GoogleException $e ) {
			throw new Exception( __( 'Unable to retrieve Google Ads recommendations.', 'google-listings-and-ads' ) . $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * Updates recommendations in the database.
	 *
	 * @param array $args Query arguments to fetch recommendations.
	 *
	 * @throws Exception If there is an error while updating recommendations.
	 */
	public function update_recommendations( $args ): void {
		try {
			$recommendations = $this->get_google_recommendations( $args );

			if ( empty( $recommendations ) ) {
				return;
			}

			/** @var AdsRecommendationsQuery $query */
			$query = $this->container->get( AdsRecommendationsQuery::class );

			// Clear existing data before updating.
			$query->reload_data();

			// Insert recommendations into the DB table.
			foreach ( $recommendations as $recommendation ) {
				$query->insert( $recommendation );
			}
		} catch ( \Exception $e ) {
			do_action( 'woocommerce_gla_debug_message', $e->getMessage(), __METHOD__ );
		}
	}
}
