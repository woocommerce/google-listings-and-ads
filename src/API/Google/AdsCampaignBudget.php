<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V6\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V6\Services\MutateCampaignBudgetResult;
use Exception;
use Google\ApiCore\ApiException;

/**
 * Class AdsCampaignBudgetTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaignBudget {

	use AdsQueryTrait;
	use AdsIdTrait;
	use MicroTrait;

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
	 * @param PositiveInteger $id
	 */
	public function __construct( GoogleAdsClient $client, PositiveInteger $id ) {
		$this->client = $client;
		$this->id     = $id;
	}

	/**
	 * Creates a new campaign budget.
	 *
	 * @param float $amount Budget amount in the local currency.
	 *
	 * @return string Resource name of the newly created budget.
	 * @throws ApiException if the campaign budget can't be created.
	 */
	public function create_campaign_budget( float $amount ): string {
		$budget = new CampaignBudget(
			[
				'amount_micros'     => $this->to_micro( $amount ),
				'explicitly_shared' => false,
			]
		);

		$operation = new CampaignBudgetOperation();
		$operation->setCreate( $budget );
		$created_budget = $this->mutate_budget( $operation );

		return $created_budget->getResourceName();
	}

	/**
	 * Updates a new campaign budget.
	 *
	 * @param int   $campaign_id Campaign ID.
	 * @param float $amount Budget amount in the local currency.
	 *
	 * @return string Resource name of the updated budget.
	 * @throws ApiException if the campaign budget can't be mutated.
	 * @throws Exception if no linked budget has been found.
	 */
	public function edit_campaign_budget( int $campaign_id, float $amount ): string {
		$budget_id = $this->get_budget_from_campaign( $campaign_id );
		$budget    = new CampaignBudget(
			[
				'resource_name' => ResourceNames::forCampaignBudget( $this->get_id(), $budget_id ),
				'amount_micros' => $this->to_micro( $amount ),
			]
		);

		$operation = new CampaignBudgetOperation();
		$operation->setUpdate( $budget );
		$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $budget ) );
		$edited_budget = $this->mutate_budget( $operation );

		return $edited_budget->getResourceName();
	}

	/**
	 * Retrieve the linked budget ID from a campaign ID.
	 *
	 * @param int $campaign_id Campaign ID.
	 *
	 * @return int
	 * @throws Exception if no linked budget has been found.
	 */
	protected function get_budget_from_campaign( int $campaign_id ): int {
		$query    = $this->build_query( [ 'campaign.campaign_budget' ], 'campaign', "campaign.id = {$campaign_id}" );
		$response = $this->query( $query );

		foreach ( $response->iterateAllElements() as $row ) {
			$campaign = $row->getCampaign();
			return $this->parse_id( $campaign->getCampaignBudget(), 'campaignBudgets' );
		}

		/* translators: %d Campaign ID */
		throw new Exception( sprintf( __( 'No budget found for campaign %d', 'google-listings-and-ads' ), $campaign_id ) );
	}

	/**
	 * Run a single mutate campaign budget operation.
	 *
	 * @param CampaignBudgetOperation $operation Operation we would like to run.
	 *
	 * @return MutateCampaignBudgetResult
	 * @throws ApiException if the remote call to mutate the campaign budget fails.
	 */
	protected function mutate_budget( CampaignBudgetOperation $operation ): MutateCampaignBudgetResult {
		$response = $this->client->getCampaignBudgetServiceClient()->mutateCampaignBudgets(
			$this->get_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}
}
