<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AdsRecommendationsQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsRecommendationsQuery as GoogleAdsRecommendationsQuery;

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
	 * Retrieves recommendations from the database for the specified type and ID.
	 *
	 * @param string $type Optional. Type of recommendation to retrieve. Currently supports only 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH'.
	 * @param int    $id   Optional. Recommendation ID to filter by. Default 0.
	 * @return array Array of recommendations.
	 */
	public function get_recommendations( string $type = '', int $id = 0 ): array {
		/** @var AdsRecommendationsQuery $query */
		$query = $this->container->get( AdsRecommendationsQuery::class );

		if ( '' === $type ) {
			$type = 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH';
		}

		// Filter by type if valid.
		if ( $type === 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH' ) {
			$query->where( 'recommendation_type', $type );
		} else {
			// If type is not valid, return an empty array.
			return [];
		}

		if ( $id ) {
			$query->where( 'recommendation_id', $id );
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

		// If no recommendations found, return an empty array.
		if ( empty( $recommendations ) ) {
			return [];
		}
		return $recommendations;
	}

	/**
	 * Retrieves recommendations from the Google Ads API.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of recommendations.
	 *
	 * @throws Exception If the merchant price benchmarks data can't be retrieved.
	 */
	public function get_google_recommendations( $args ): array {
		try {
			$response = ( new GoogleAdsRecommendationsQuery( $args ) )
			->set_client( $this->client, $this->options->get_ads_id() )
			->get_results();

			if ( empty( $response ) ) {
				return [];
			}

			return $response;
		} catch ( GoogleException $e ) {
			throw new Exception( __( 'Unable to retrieve Google Ads recommendations.', 'google-listings-and-ads' ) . $e->getMessage(), $e->getCode() );
		}
	}
}
