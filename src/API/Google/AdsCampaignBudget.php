<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignBudgetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V9\ResourceNames;
use Google\Ads\GoogleAds\V9\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V9\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V9\Services\CampaignBudgetServiceClient;
use Google\Ads\GoogleAds\V9\Services\MutateOperation;
use Google\ApiCore\ValidationException;
use Exception;

/**
 * Class AdsCampaignBudget
 *
 * @since 1.12.2 Refactored to support PMax and (legacy) SSC.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaignBudget implements OptionsAwareInterface {

	use MicroTrait;
	use OptionsAwareTrait;

	/**
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected const TEMPORARY_ID = -2;

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
	 * Returns a new campaign budget create operation.
	 *
	 * @param string $campaign_name New campaign name.
	 * @param float  $amount        Budget amount in the local currency.
	 *
	 * @return MutateOperation
	 */
	public function create_operation( string $campaign_name, float $amount ): MutateOperation {
		$budget = new CampaignBudget(
			[
				'resource_name'     => $this->temporary_resource_name(),
				'name'              => $campaign_name . ' Budget',
				'amount_micros'     => $this->to_micro( $amount ),
				'explicitly_shared' => false,
			]
		);

		$operation = ( new CampaignBudgetOperation() )->setCreate( $budget );
		return ( new MutateOperation() )->setCampaignBudgetOperation( $operation );
	}

	/**
	 * Updates a new campaign budget.
	 *
	 * @param int   $campaign_id Campaign ID.
	 * @param float $amount Budget amount in the local currency.
	 *
	 * @return string Resource name of the updated budget.
	 * @throws Exception If no linked budget has been found.
	 */
	public function edit_operation( int $campaign_id, float $amount ): MutateOperation {
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
		return ( new MutateOperation() )->setCampaignBudgetOperation( $operation );
	}

	/**
	 * Return a temporary resource name for the campaign budget.
	 *
	 * @return string
	 */
	public function temporary_resource_name() {
		return ResourceNames::forCampaignBudget( $this->options->get_ads_id(), self::TEMPORARY_ID );
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
