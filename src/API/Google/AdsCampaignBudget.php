<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignBudgetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V6\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V6\Services\CampaignBudgetServiceClient;
use Google\Ads\GoogleAds\V6\Services\MutateCampaignBudgetResult;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Exception;

/**
 * Class AdsCampaignBudget
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaignBudget implements OptionsAwareInterface {

	use MicroTrait;
	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * AdsCampaignBudget constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Creates a new campaign budget.
	 *
	 * @param float $amount Budget amount in the local currency.
	 *
	 * @return string Resource name of the newly created budget.
	 * @throws ApiException If the campaign budget can't be created.
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
	 * @throws ApiException If the campaign budget can't be mutated.
	 * @throws Exception If no linked budget has been found.
	 */
	public function edit_campaign_budget( int $campaign_id, float $amount ): string {
		$budget_id = $this->get_budget_from_campaign( $campaign_id );
		$budget    = new CampaignBudget(
			[
				'resource_name' => ResourceNames::forCampaignBudget( $this->options->get_ads_id(), $budget_id ),
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
	 * @param int $campaign_id
	 *
	 * @return array resource names of deleted budgets.
	 * @throws ApiException If the budget isn't deleted.
	 * @throws Exception If no linked budget has been found.
	 */
	public function delete_campaign_budget( int $campaign_id ): array {
		$budget_id            = $this->get_budget_from_campaign( $campaign_id );
		$budget_resource_name = ResourceNames::forCampaignBudget( $this->options->get_ads_id(), $budget_id );

		$operation = new CampaignBudgetOperation();
		$operation->setRemove( $budget_resource_name );
		$deleted_budget = $this->mutate_budget( $operation );
		return [ $deleted_budget->getResourceName() ];
	}

	/**
	 * Retrieve the linked budget ID from a campaign ID.
	 *
	 * @param int $campaign_id Campaign ID.
	 *
	 * @return int
	 * @throws Exception If no linked budget has been found.
	 */
	protected function get_budget_from_campaign( int $campaign_id ): int {
		$results = ( new AdsCampaignBudgetQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'campaign.id', $campaign_id )
			->get_results();

		foreach ( $results->iterateAllElements() as $row ) {
			$campaign = $row->getCampaign();
			return $this->parse_campaign_budget_id( $campaign->getCampaignBudget() );
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
	 * @throws ApiException If the remote call to mutate the campaign budget fails.
	 */
	protected function mutate_budget( CampaignBudgetOperation $operation ): MutateCampaignBudgetResult {
		$response = $this->client->getCampaignBudgetServiceClient()->mutateCampaignBudgets(
			$this->options->get_ads_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}

	/**
	 * Convert ID from a resource name to an int.
	 *
	 * @param string $name Resource name containing ID number.
	 *
	 * @return int
	 * @throws Exception When unable to parse resource ID.
	 */
	protected function parse_campaign_budget_id( string $name ): int {
		try {
			$parts = CampaignBudgetServiceClient::parseName( $name );
			return absint( $parts['campaign_budget_id'] );
		} catch ( ValidationException $e ) {
			throw new Exception( __( 'Invalid campaign budget ID', 'google-listings-and-ads' ) );
		}
	}
}
