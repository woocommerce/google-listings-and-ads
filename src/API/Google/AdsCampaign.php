<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V9\ResourceNames;
use Google\Ads\GoogleAds\V9\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V9\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V9\Resources\Campaign;
use Google\Ads\GoogleAds\V9\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V9\Services\CampaignServiceClient;
use Google\Ads\GoogleAds\V9\Services\CampaignOperation;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V9\Services\MutateOperation;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Exception;

/**
 * Class AdsCampaign (Performance Max Campaign)
 * https://developers.google.com/google-ads/api/docs/performance-max/overview
 *
 * ContainerAware used for:
 * - AdsAssetGroup
 * - AdsGroup
 *
 * @since x.x.x Refactored to support PMax and (legacy) SSC.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaign implements ContainerAwareInterface, OptionsAwareInterface {

	use ApiExceptionTrait;
	use ContainerAwareTrait;
	use OptionsAwareTrait;
	use MicroTrait;

	/**
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected const TEMPORARY_ID = -1;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * @var AdsCampaignBudget $budget
	 */
	protected $budget;

	/**
	 * AdsCampaign constructor.
	 *
	 * @param GoogleAdsClient   $client
	 * @param AdsCampaignBudget $budget
	 */
	public function __construct( GoogleAdsClient $client, AdsCampaignBudget $budget ) {
		$this->client = $client;
		$this->budget = $budget;
	}

	/**
	 * Returns a list of campaigns
	 *
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
				$this->map_grpc_code_to_http_status_code( $e )
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
				$this->map_grpc_code_to_http_status_code( $e )
			);
		}
	}

	/**
	 * Create a new campaign.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 * @throws Exception When an ApiException is caught.
	 */
	public function create_campaign( array $params ): array {
		try {
			// Operations must be in a specific order to match the temporary ID's.
			$operations = array_merge(
				[ $this->budget->create_operation( $params['name'], $params['amount'] ) ],
				[ $this->create_operation( $params['name'], $params['country'] ) ],
				$this->container->get( AdsAssetGroup::class )->create_operations(
					$this->temporary_resource_name(),
					$params['name']
				)
			);

			$campaign_id = $this->mutate( $operations );

			return [
				'id'     => $campaign_id,
				'status' => CampaignStatus::ENABLED,
				'type'   => CampaignType::PERFORMANCE_MAX,
			] + $params;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			if ( $this->has_api_exception_error( $e, 'DUPLICATE_CAMPAIGN_NAME' ) ) {
				throw new Exception(
					__( 'A campaign with this name already exists', 'google-listings-and-ads' ),
					$this->map_grpc_code_to_http_status_code( $e )
				);
			}

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error creating campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$this->map_grpc_code_to_http_status_code( $e )
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
			$operations      = [];
			$campaign_fields = [];

			if ( ! empty( $params['name'] ) ) {
				$campaign_fields['name'] = $params['name'];
			}

			if ( ! empty( $params['status'] ) ) {
				$campaign_fields['status'] = CampaignStatus::number( $params['status'] );
			}

			if ( ! empty( $params['amount'] ) ) {
				$operations[] = $this->budget->edit_operation( $campaign_id, $params['amount'] );
			}

			if ( ! empty( $campaign_fields ) ) {
				$operations[] = $this->edit_operation( $campaign_id, $campaign_fields );
			}

			if ( ! empty( $operations ) ) {
				return $this->mutate( $operations ) ?: $campaign_id;
			}

			return $campaign_id;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error editing campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$this->map_grpc_code_to_http_status_code( $e )
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
			$campaign_resource_name = ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id );

			$asset_group_operations = $this->container->get( AdsAssetGroup::class )->delete_operations( $campaign_resource_name );

			// Retrieve legacy SSC ad group if we haven't gotten any asset group.
			$ad_group_operations = empty( $asset_group_operations ) ?
				$this->container->get( AdsGroup::class )->delete_operations( $campaign_resource_name ) :
				[];

			// Include operations for AdsGroup (legacy SSC)
			$operations = array_merge(
				$asset_group_operations,
				$ad_group_operations,
				[ $this->delete_operation( $campaign_resource_name ) ],
				[ $this->budget->delete_operation( $campaign_id ) ]
			);

			return $this->mutate( $operations );
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			if ( $this->has_api_exception_error( $e, 'OPERATION_NOT_PERMITTED_FOR_REMOVED_RESOURCE' ) ) {
				throw new Exception(
					__( 'This campaign has already been deleted', 'google-listings-and-ads' ),
					$this->map_grpc_code_to_http_status_code( $e )
				);
			}

			throw new Exception(
				/* translators: %s Error message */
				sprintf( __( 'Error deleting campaign: %s', 'google-listings-and-ads' ), $e->getBasicMessage() ),
				$this->map_grpc_code_to_http_status_code( $e )
			);
		}
	}

	/**
	 * Return a temporary resource name for the campaign.
	 *
	 * @return string
	 */
	public function temporary_resource_name() {
		return ResourceNames::forCampaign( $this->options->get_ads_id(), self::TEMPORARY_ID );
	}

	/**
	 * Returns a campaign create operation.
	 *
	 * @param string $campaign_name
	 * @param string $country
	 *
	 * @return MutateOperation
	 */
	protected function create_operation( string $campaign_name, string $country ): MutateOperation {
		$campaign = new Campaign(
			[
				'resource_name'             => $this->temporary_resource_name(),
				'name'                      => $campaign_name,
				'advertising_channel_type'  => AdvertisingChannelType::PERFORMANCE_MAX,
				'status'                    => CampaignStatus::number( 'enabled' ),
				'campaign_budget'           => $this->budget->temporary_resource_name(),
				'maximize_conversion_value' => new MaximizeConversionValue(),
				'url_expansion_opt_out'     => true,
				'shopping_setting'          => new ShoppingSetting(
					[
						'merchant_id'   => $this->options->get_merchant_id(),
						'sales_country' => $country,
					]
				),
			]
		);

		$operation = ( new CampaignOperation() )->setCreate( $campaign );
		return ( new MutateOperation() )->setCampaignOperation( $operation );
	}

	/**
	 * Returns a campaign edit operation.
	 *
	 * @param integer $campaign_id
	 * @param array   $fields
	 *
	 * @return MutateOperation
	 */
	protected function edit_operation( int $campaign_id, array $fields ): MutateOperation {
		$fields['resource_name'] = ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id );

		$campaign  = new Campaign( $fields );
		$operation = new CampaignOperation();
		$operation->setUpdate( $campaign );
		$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $campaign ) );
		return ( new MutateOperation() )->setCampaignOperation( $operation );
	}

	/**
	 * Returns a campaign delete operation.
	 *
	 * @param string $campaign_resource_name
	 *
	 * @return MutateOperation
	 */
	protected function delete_operation( string $campaign_resource_name ): MutateOperation {
		$operation = ( new CampaignOperation() )->setRemove( $campaign_resource_name );
		return ( new MutateOperation() )->setCampaignOperation( $operation );
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
			'type'   => CampaignType::label( $campaign->getAdvertisingChannelType() ),
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
	 * Send a batch of operations to mutate a campaign.
	 *
	 * @param MutateOperation[] $operations
	 *
	 * @return int Campaign ID from the MutateOperationResponse.
	 * @throws ApiException If any of the operations fail.
	 */
	protected function mutate( array $operations ): int {
		$responses = $this->client->getGoogleAdsServiceClient()->mutate(
			$this->options->get_ads_id(),
			$operations
		);

		foreach ( $responses->getMutateOperationResponses() as $response ) {
			if ( 'campaign_result' === $response->getResponse() ) {
				$campaign_result = $response->getCampaignResult();
				return $this->parse_campaign_id( $campaign_result->getResourceName() );
			}
		}

		// When editing only the budget there is no campaign mutate result.
		return 0;
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
