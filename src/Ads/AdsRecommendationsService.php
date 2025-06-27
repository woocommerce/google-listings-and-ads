<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AdsRecommendationsQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsRecommendationsService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AdsRecommendationsService implements ContainerAwareInterface, Service {

	use ContainerAwareTrait;

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
				'id'             => (int) ( $item['recommendation_id'] ?? 0 ),
				'type'           => $item['recommendation_type'] ?? '',
				'resource_name'  => $item['recommendation_resource_name'] ?? '',
				'campaign_id'    => $item['recommendation_campaign_id'] ?? '',
				'campaign_name'  => $item['recommendation_campaign_name'] ?? '',
				'campaign_status'=> $item['recommendation_campaign_status'] ?? '',
				'last_synced'    => isset( $item['recommendation_last_synced'] )
					? date( 'c', strtotime( $item['recommendation_last_synced'] ) )
					: null,
			];
		}

		// If no recommendations found, return an empty array.
		if ( empty( $recommendations ) ) {
			return [];
		}
		return $recommendations;
	}
}
