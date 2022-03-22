<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignCriterionQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\V9\ResourceNames;
use Google\Ads\GoogleAds\V9\Common\LocationInfo;
use Google\Ads\GoogleAds\V9\Enums\CampaignCriterionStatusEnum\CampaignCriterionStatus;
use Google\Ads\GoogleAds\V9\Resources\CampaignCriterion;
use Google\Ads\GoogleAds\V9\Services\CampaignCriterionOperation;
use Google\Ads\GoogleAds\V9\Services\MutateOperation;

/**
 * Class AdsCampaignCriterion
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaignCriterion implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * AdsCampaignCriterion constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

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
	 * Returns a set of operations to delete multiple campaign criteria.
	 *
	 * @param string $campaign_resource_name
	 * @return array
	 */
	public function delete_operations( string $campaign_resource_name ): array {
		$operations = [];

		$results = ( new AdsCampaignCriterionQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'campaign_criterion.campaign', $campaign_resource_name )
			->where( 'campaign_criterion.status', 'REMOVED', '!=' )
			->where( 'campaign_criterion.location.geo_target_constant', '', 'IS NOT NULL' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $results->iterateAllElements() as $row ) {
			$criterion               = $row->getCampaignCriterion();
			$criterion_resource_name = $criterion->getResourceName();

			$operation    = ( new CampaignCriterionOperation() )->setRemove( $criterion_resource_name );
			$operations[] = ( new MutateOperation() )->setCampaignCriterionOperation( $operation );
		}

		return $operations;
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
