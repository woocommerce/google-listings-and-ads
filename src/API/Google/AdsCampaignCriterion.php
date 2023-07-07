<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\Util\V13\ResourceNames;
use Google\Ads\GoogleAds\V13\Common\LocationInfo;
use Google\Ads\GoogleAds\V13\Enums\CampaignCriterionStatusEnum\CampaignCriterionStatus;
use Google\Ads\GoogleAds\V13\Resources\CampaignCriterion;
use Google\Ads\GoogleAds\V13\Services\CampaignCriterionOperation;
use Google\Ads\GoogleAds\V13\Services\MutateOperation;

/**
 * Class AdsCampaignCriterion
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaignCriterion {

	use ExceptionTrait;

	/**
	 * Returns a set of operations to create multiple campaign criteria.
	 *
	 * @param string $campaign_resource_name Campaign resource name.
	 * @param array  $location_ids           Targeted locations IDs.
	 *
	 * @return array
	 */
	public function create_operations( string $campaign_resource_name, array $location_ids ): array {
		return array_map(
			function ( $location_id ) use ( $campaign_resource_name ) {
				return $this->create_operation( $campaign_resource_name, $location_id );
			},
			$location_ids
		);
	}

	/**
	 * Returns a new campaign criterion create operation.
	 *
	 * @param string $campaign_resource_name Campaign resource name.
	 * @param int    $location_id            Targeted location ID.
	 *
	 * @return MutateOperation
	 */
	protected function create_operation( string $campaign_resource_name, int $location_id ): MutateOperation {
		$campaign_criterion = new CampaignCriterion(
			[
				'campaign' => $campaign_resource_name,
				'negative' => false,
				'status'   => CampaignCriterionStatus::ENABLED,
				'location' => new LocationInfo(
					[
						'geo_target_constant' => ResourceNames::forGeoTargetConstant( $location_id ),
					]
				),
			]
		);

		$operation = ( new CampaignCriterionOperation() )->setCreate( $campaign_criterion );
		return ( new MutateOperation() )->setCampaignCriterionOperation( $operation );
	}
}
