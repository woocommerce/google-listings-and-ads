<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V8\ResourceNames;
use Google\Ads\GoogleAds\V8\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V8\Enums\AdvertisingChannelSubTypeEnum\AdvertisingChannelSubType;
use Google\Ads\GoogleAds\V8\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V8\Resources\Campaign;
use Google\Ads\GoogleAds\V8\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V8\Services\CampaignOperation;
use Google\Ads\GoogleAds\V8\Services\CampaignServiceClient;
use Google\Ads\GoogleAds\V8\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V8\Services\MutateCampaignResult;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Exception;

/**
 * Class AdsCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaign implements OptionsAwareInterface {

	use ApiExceptionTrait;
	use OptionsAwareTrait;
	use MicroTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * @var AdsCampaignBudget $ads_campaign_budget
	 */
	protected $ads_campaign_budget;

	/**
	 * @var AdsGroup $ads_group
	 */
	protected $ads_group;


	/**
	 * AdsCampaign constructor.
	 *
	 * @param GoogleAdsClient   $client
	 * @param AdsCampaignBudget $ads_campaign_budget
	 * @param AdsGroup          $ads_group
	 */
	public function __construct( GoogleAdsClient $client, AdsCampaignBudget $ads_campaign_budget, AdsGroup $ads_group ) {
		$this->client              = $client;
		$this->ads_campaign_budget = $ads_campaign_budget;
		$this->ads_group           = $ads_group;
	}

	/**
	 * @return array
	 * @throws Exception When an ApiException is caught.
	 */
	public function get_campaigns(): array {
		try {
			$return  = [];
			$results = ( new AdsCampaignQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'campaign.status', 'REMOVED', '!=' )
				->get_results();

			foreach ( $results->iterateAllElements() as $row ) {
				$return[] = $this->convert_campaign( $row );
			}

			return $return;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving campaigns: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$e->getCode()
			);
		}
	}


	/**
	 * Create a new campaign.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 * @throws Exception When an ApiException is caught or the created ID is invalid.
	 */
	public function create_campaign( array $params ): array {
		try {
			$budget = $this->ads_campaign_budget->create_campaign_budget( $params['amount'] );

			$campaign = new Campaign(
				[
					'name'                         => $params['name'],
					'advertising_channel_type'     => AdvertisingChannelType::SHOPPING,
					'advertising_channel_sub_type' => AdvertisingChannelSubType::SHOPPING_SMART_ADS,
					'status'                       => CampaignStatus::number( 'enabled' ),
					'campaign_budget'              => $budget,
					'maximize_conversion_value'    => new MaximizeConversionValue(),
					'shopping_setting'             => new ShoppingSetting(
						[
							'merchant_id'   => $this->options->get_merchant_id(),
							'sales_country' => $params['country'],
						]
					),
				]
			);

			$operation = new CampaignOperation();
			$operation->setCreate( $campaign );
			$created_campaign = $this->mutate_campaign( $operation );
			$campaign_id      = $this->parse_campaign_id( $created_campaign->getResourceName() );

			$this->ads_group->set_up_for_campaign( $created_campaign->getResourceName(), $params['name'] );

			return [
				'id'     => $campaign_id,
				'status' => CampaignStatus::ENABLED,
			] + $params;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			if ( $this->has_api_exception_error( $e, 'DUPLICATE_CAMPAIGN_NAME' ) ) {
				throw new Exception(
					__( 'A campaign with this name already exists', 'google-listings-and-ads' ),
					$e->getCode()
				);
			}

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error creating campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$e->getCode()
			);
		}
	}

	/**
	 * Retrieve a single campaign.
	 *
	 * @param int $id Campaign ID.
	 *
	 * @return array
	 * @throws Exception When an ApiException is caught.
	 */
	public function get_campaign( int $id ): array {
		try {
			$results = ( new AdsCampaignQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'campaign.id', $id )
				->get_results();

			foreach ( $results->iterateAllElements() as $row ) {
				return $this->convert_campaign( $row );
			}

			return [];
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$e->getCode()
			);
		}
	}

	/**
	 * Edit a campaign.
	 *
	 * @param int   $campaign_id Campaign ID.
	 * @param array $params      Request parameters.
	 *
	 * @return int
	 * @throws Exception When an ApiException is caught or the ID is invalid.
	 */
	public function edit_campaign( int $campaign_id, array $params ): int {
		try {
			$campaign_fields = [
				'resource_name' => ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id ),
			];
			if ( ! empty( $params['name'] ) ) {
				$campaign_fields['name'] = $params['name'];
			}
			if ( ! empty( $params['status'] ) ) {
				$campaign_fields['status'] = CampaignStatus::number( $params['status'] );
			}
			if ( ! empty( $params['amount'] ) ) {
				$this->ads_campaign_budget->edit_campaign_budget( $campaign_id, $params['amount'] );
			}

			if ( count( $campaign_fields ) > 1 ) {
				$campaign  = new Campaign( $campaign_fields );
				$operation = new CampaignOperation();
				$operation->setUpdate( $campaign );
				$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $campaign ) );
				$edited_campaign = $this->mutate_campaign( $operation );
				$campaign_id     = $this->parse_campaign_id( $edited_campaign->getResourceName() );
			}

			return $campaign_id;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error editing campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$e->getCode()
			);
		}
	}

	/**
	 * Delete a campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 *
	 * @return int
	 * @throws Exception When an ApiException is caught or the ID is invalid.
	 */
	public function delete_campaign( int $campaign_id ): int {
		try {
			$resource_name = ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id );

			$this->ads_group->delete_for_campaign( $resource_name );

			$operation = new CampaignOperation();
			$operation->setRemove( $resource_name );
			$deleted_campaign = $this->mutate_campaign( $operation );

			$this->ads_campaign_budget->delete_campaign_budget( $campaign_id );

			return $this->parse_campaign_id( $deleted_campaign->getResourceName() );
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			if ( $this->has_api_exception_error( $e, 'OPERATION_NOT_PERMITTED_FOR_REMOVED_RESOURCE' ) ) {
				throw new Exception(
					__( 'This campaign has already been deleted', 'google-listings-and-ads' ),
					$e->getCode()
				);
			}

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error deleting campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$e->getCode()
			);
		}
	}

	/**
	 * Convert campaign data to an array.
	 *
	 * @param GoogleAdsRow $row Data row returned from a query request.
	 *
	 * @return array
	 */
	protected function convert_campaign( GoogleAdsRow $row ): array {
		$campaign = $row->getCampaign();
		$data     = [
			'id'     => $campaign->getId(),
			'name'   => $campaign->getName(),
			'status' => CampaignStatus::label( $campaign->getStatus() ),
		];

		$budget = $row->getCampaignBudget();
		if ( $budget ) {
			$data += [
				'amount' => $this->from_micro( $budget->getAmountMicros() ),
			];
		}

		$shopping = $campaign->getShoppingSetting();
		if ( $shopping ) {
			$data += [
				'country' => $shopping->getSalesCountry(),
			];
		}

		return $data;
	}

	/**
	 * Run a single mutate campaign operation.
	 *
	 * @param CampaignOperation $operation Operation we would like to run.
	 *
	 * @return MutateCampaignResult
	 * @throws ApiException If the campaign mutate fails.
	 */
	protected function mutate_campaign( CampaignOperation $operation ): MutateCampaignResult {
		$response = $this->client->getCampaignServiceClient()->mutateCampaigns(
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
	protected function parse_campaign_id( string $name ): int {
		try {
			$parts = CampaignServiceClient::parseName( $name );
			return absint( $parts['campaign_id'] );
		} catch ( ValidationException $e ) {
			throw new Exception( __( 'Invalid campaign ID', 'google-listings-and-ads' ) );
		}
	}
}
