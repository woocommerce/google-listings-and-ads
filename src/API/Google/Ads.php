<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelSubTypeEnum\AdvertisingChannelSubType;
use Google\Ads\GoogleAds\V6\Enums\MerchantCenterLinkStatusEnum\MerchantCenterLinkStatus;
use Google\Ads\GoogleAds\V6\Resources\Campaign;
use Google\Ads\GoogleAds\V6\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V6\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V6\Resources\MerchantCenterLink;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V6\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V6\Services\CampaignOperation;
use Google\Ads\GoogleAds\V6\Services\MerchantCenterLinkOperation;
use Google\Ads\GoogleAds\V6\Services\MutateCampaignResult;
use Google\Ads\GoogleAds\V6\Services\MutateCampaignBudgetResult;
use Google\ApiCore\ApiException;
use Google\ApiCore\PagedListResponse;
use Psr\Container\ContainerInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ads
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Ads {

	use MicroTrait;

	/**
	 * The container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * The ads account ID.
	 *
	 * @var PositiveInteger
	 */
	protected $id;

	/**
	 * Ads constructor.
	 *
	 * @param ContainerInterface $container
	 * @param PositiveInteger    $id
	 */
	public function __construct( ContainerInterface $container, PositiveInteger $id ) {
		$this->container = $container;
		$this->id        = $id;
	}

	/**
	 * Get the ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id->get();
	}

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
			$deleted_campaign    = $this->mutate_campaign( $operation );
			$deleted_campaign_id = $this->parse_id( $deleted_campaign->getResourceName(), 'campaigns' );

			return $deleted_campaign_id;
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
	 * Creates a new campaign budget.
	 *
	 * @param float $amount Budget amount in the local currency.
	 *
	 * @return string Resource name of the newly created budget.
	 */
	protected function create_campaign_budget( float $amount ): string {
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
	 * @param float $amount      Budget amount in the local currency.
	 *
	 * @return string Resource name of the updated budget.
	 */
	protected function edit_campaign_budget( int $campaign_id, float $amount ): string {
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
	 * @throws Exception When no linked budget has been found.
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
			[ $operation ],
			$this->get_args()
		);

		return $response->getResults()[0];
	}

	/**
	 * Run a single mutate campaign budget operation.
	 *
	 * @param CampaignBudgetOperation $operation Operation we would like to run.
	 *
	 * @return MutateCampaignBudgetResult
	 */
	protected function mutate_budget( CampaignBudgetOperation $operation ): MutateCampaignBudgetResult {
		$client   = $this->container->get( GoogleAdsClient::class );
		$response = $client->getCampaignBudgetServiceClient()->mutateCampaignBudgets(
			$this->get_id(),
			[ $operation ],
			$this->get_args()
		);

		return $response->getResults()[0];
	}

	/**
	 * Build a Google Ads Query string.
	 *
	 * @param array  $fields   List of fields to return.
	 * @param string $resource Resource name to query from.
	 * @param string $where    Condition clause.
	 *
	 * @return string
	 */
	protected function build_query( array $fields, string $resource, string $where = '' ): string {
		$joined = join( ',', $fields );
		$query  = "SELECT {$joined} FROM {$resource}";

		if ( ! empty( $where ) ) {
			$query .= " WHERE {$where}";
		}

		return $query;
	}

	/**
	 * Run a Google Ads Query.
	 *
	 * @param string $query Query to run.
	 *
	 * @return PagedListResponse
	 */
	protected function query( string $query ): PagedListResponse {
		/** @var GoogleAdsClient $client */
		$client = $this->container->get( GoogleAdsClient::class );

		return $client->getGoogleAdsServiceClient()->search( $this->get_id(), $query, $this->get_args() );
	}

	/**
	 * Get request arguments including authentication headers.
	 *
	 * @return array
	 */
	protected function get_args(): array {
		return [ 'headers' => $this->container->get( 'connect_server_auth_header' ) ];
	}

	/**
	 * Convert ID from a resource name to an int.
	 *
	 * @param string $name     Resource name containing ID number.
	 * @param string $resource Resource type.
	 *
	 * @return int
	 * @throws Exception When unable to parse resource ID.
	 */
	protected function parse_id( string $name, string $resource ): int {
		if ( ! preg_match( '/' . preg_quote( $resource, '/' ) . '\/([0-9]+)/', $name, $matches ) || empty( $matches[1] ) ) {
			throw new Exception( __( 'Invalid resource ID', 'google-listings-and-ads' ) );
		}

		return absint( $matches[1] );
	}

	/**
	 * Check if the ApiException contains a specific error.
	 *
	 * @param ApiException $exception  Exception to check.
	 * @param string       $error_code Error code we are checking.
	 *
	 * @return bool
	 */
	protected function has_api_exception_error( ApiException $exception, string $error_code ): bool {
		$meta = $exception->getMetadata();
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			return false;
		}

		foreach ( $meta as $data ) {
			if ( empty( $data['errors'] || ! is_array( $data['errors'] ) ) ) {
				continue;
			}

			foreach ( $data['errors'] as $error ) {
				if ( in_array( $error_code, $error['errorCode'], true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the Merchant Center ID.
	 *
	 * @return int
	 */
	protected function get_merchant_id(): int {
		/** @var Options $options */
		$options = $this->container->get( OptionsInterface::class );
		return $options->get( Options::MERCHANT_ID );
	}

	/**
	 * Accept a link from a merchant account.
	 *
	 * @param int $merchant_id Merchant Center account id.
	 * @throws Exception When a link has already been accepted or is unavailable.
	 */
	public function accept_merchant_link( int $merchant_id ) {
		$link = $this->get_merchant_link( $merchant_id );

		if ( $link->getStatus() === MerchantCenterLinkStatus::ENABLED ) {
			throw new Exception( __( 'Merchant link has already been accepted', 'google-listings-and-ads' ) );
		}

		$link->setStatus( MerchantCenterLinkStatus::ENABLED );

		$operation = new MerchantCenterLinkOperation();
		$operation->setUpdate( $link );
		$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $link ) );

		$client   = $this->container->get( GoogleAdsClient::class );
		$response = $client->getMerchantCenterLinkServiceClient()->mutateMerchantCenterLink(
			$this->get_id(),
			$operation,
			$this->get_args()
		);
	}

	/**
	 * Get the link from a merchant account.
	 *
	 * @param int $merchant_id Merchant Center account id.
	 *
	 * @return MerchantCenterLink
	 * @throws Exception When the merchant link hasn't been created.
	 */
	private function get_merchant_link( int $merchant_id ): MerchantCenterLink {
		$client   = $this->container->get( GoogleAdsClient::class );
		$response = $client->getMerchantCenterLinkServiceClient()->listMerchantCenterLinks(
			$this->get_id(),
			$this->get_args()
		);

		foreach ( $response->getMerchantCenterLinks() as $link ) {
			if ( $merchant_id === absint( $link->getId() ) ) {
				return $link;
			}
		}

		throw new Exception( __( 'Merchant link is not available to accept', 'google-listings-and-ads' ) );
	}
}
