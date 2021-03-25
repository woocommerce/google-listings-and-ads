<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelSubTypeEnum\AdvertisingChannelSubType;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V6\Resources\Campaign;
use Google\Ads\GoogleAds\V6\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V6\Services\CampaignOperation;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V6\Services\MutateCampaignResult;
use Google\ApiCore\ApiException;
use Exception;

/**
 * Trait AdsCampaignTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait AdsCampaignTrait {

	use AdsQueryTrait;
	use ApiExceptionTrait;
	use MicroTrait;

	/**
	 * @return array
	 * @throws Exception When an ApiException is caught.
	 */
	public function get_campaigns(): array {
		try {
			$return   = [];
			$response = $this->query( $this->get_campaign_query() );

			foreach ( $response->iterateAllElements() as $row ) {
				$return[] = $this->convert_campaign( $row );
			}

			return $return;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error retrieving campaigns: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ) );
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
			$budget = $this->create_campaign_budget( $params['amount'] );

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
							'merchant_id'   => $this->get_merchant_id(),
							'sales_country' => $params['country'],
						]
					),
				]
			);

			$operation = new CampaignOperation();
			$operation->setCreate( $campaign );
			$created_campaign = $this->mutate_campaign( $operation );
			$campaign_id      = $this->parse_id( $created_campaign->getResourceName(), 'campaigns' );

			return [
				'id'     => $campaign_id,
				'status' => CampaignStatus::ENABLED,
			] + $params;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			if ( $this->has_api_exception_error( $e, 'DUPLICATE_CAMPAIGN_NAME' ) ) {
				throw new Exception( __( 'A campaign with this name already exists', 'google-listings-and-ads' ) );
			}

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error creating campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ) );
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
			$response = $this->query( $this->get_campaign_query( $id ) );

			foreach ( $response->iterateAllElements() as $row ) {
				return $this->convert_campaign( $row );
			}

			return [];
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error retrieving campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ) );
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
				'resource_name' => ResourceNames::forCampaign( $this->get_id(), $campaign_id ),
			];
			if ( ! empty( $params['name'] ) ) {
				$campaign_fields['name'] = $params['name'];
			}
			if ( ! empty( $params['status'] ) ) {
				$campaign_fields['status'] = CampaignStatus::number( $params['status'] );
			}
			if ( ! empty( $params['amount'] ) ) {
				$this->edit_campaign_budget( $campaign_id, $params['amount'] );
			}

			if ( count( $campaign_fields ) > 1 ) {
				$campaign  = new Campaign( $campaign_fields );
				$operation = new CampaignOperation();
				$operation->setUpdate( $campaign );
				$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $campaign ) );
				$edited_campaign = $this->mutate_campaign( $operation );
				$campaign_id     = $this->parse_id( $edited_campaign->getResourceName(), 'campaigns' );
			}

			return $campaign_id;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error editing campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ) );
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
			$resource_name = ResourceNames::forCampaign( $this->get_id(), $campaign_id );
			$operation     = new CampaignOperation();
			$operation->setRemove( $resource_name );
			$deleted_campaign = $this->mutate_campaign( $operation );
			return $this->parse_id( $deleted_campaign->getResourceName(), 'campaigns' );
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			if ( $this->has_api_exception_error( $e, 'OPERATION_NOT_PERMITTED_FOR_REMOVED_RESOURCE' ) ) {
				throw new Exception( __( 'This campaign has already been deleted', 'google-listings-and-ads' ) );
			}

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error deleting campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ) );
		}
	}

	/**
	 * Get campaign query.
	 *
	 * @param int $id Optional ID to retrieve a specific campaign.
	 *
	 * @return string
	 */
	protected function get_campaign_query( int $id = 0 ): string {
		return $this->build_query(
			[
				'campaign.id',
				'campaign.name',
				'campaign.status',
				'campaign.shopping_setting.sales_country',
				'campaign_budget.amount_micros',
			],
			'campaign',
			$id ? "campaign.id = {$id}" : "campaign.status != 'REMOVED'"
		);
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
	 */
	protected function mutate_campaign( CampaignOperation $operation ): MutateCampaignResult {
		$client   = $this->container->get( GoogleAdsClient::class );
		$response = $client->getCampaignServiceClient()->mutateCampaigns(
			$this->get_id(),
			[ $operation ]
		);

		return $response->getResults()[0];
	}

}
