<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsException;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelSubTypeEnum\AdvertisingChannelSubType;
use Google\Ads\GoogleAds\V6\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V6\Resources\Campaign;
use Google\Ads\GoogleAds\V6\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V6\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V6\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V6\Services\CampaignOperation;
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

			throw new Exception( sprintf( 'Error retrieving campaigns: %s', $e->getBasicMessage() ) );
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
					'status'                       => CampaignStatus::ENABLED,
					'campaign_budget'              => $budget,
					'maximize_conversion_value'    => new MaximizeConversionValue(),
					'shopping_setting'             => new ShoppingSetting(
						[
							'merchant_id'   => $this->container->get( 'merchant_id' ),
							'sales_country' => $params['country'],
						]
					),
				]
			);

			$operation = new CampaignOperation();
			$operation->setCreate( $campaign );

			/** @var GoogleAdsClient $client */
			$client   = $this->container->get( GoogleAdsClient::class );
			$response = $client->getCampaignServiceClient()->mutateCampaigns(
				$this->get_id(),
				[ $operation ],
				$this->get_args()
			);

			/** @var Campaign $created_campaign */
			$created_campaign = $response->getResults()[0];
			$campaign_id      = $this->parse_id( $created_campaign->getResourceName() );

			return [ 'id' => $campaign_id ] + $params;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			if ( $this->has_api_exception_error( $e, 'DUPLICATE_CAMPAIGN_NAME' ) ) {
				throw new Exception( 'A campaign with this name already exists' );
			}

			throw new Exception( sprintf( 'Error creating campaign: %s', $e->getBasicMessage() ) );
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

			throw new Exception( sprintf( 'Error retrieving campaign: %s', $e->getBasicMessage() ) );
		}
	}

	/**
	 * Delete a campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 *
	 * @return int
	 * @throws Exception When an ApiException is caught or the created ID is invalid.
	 */
	public function delete_campaign( int $campaign_id ): int {
		try {
			$resource_name = ResourceNames::forCampaign( $this->get_id(), $campaign_id );
			$operation     = new CampaignOperation();
			$operation->setRemove( $resource_name );

			/** @var GoogleAdsClient $client */
			$client   = $this->container->get( GoogleAdsClient::class );
			$response = $client->getCampaignServiceClient()->mutateCampaigns(
				$this->get_id(),
				[ $operation ],
				$this->get_args()
			);

			/** @var Campaign $deleted_campaign */
			$deleted_campaign    = $response->getResults()[0];
			$deleted_campaign_id = $this->parse_id( $deleted_campaign->getResourceName() );

			return $deleted_campaign_id;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			throw new Exception( sprintf( 'Error deleting campaign: %s', $e->getBasicMessage() ) );
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
				'campaign.shopping_setting.sales_country',
				'campaign_budget.amount_micros',
			],
			'campaign',
			$id ? "campaign.id = {$id}" : ''
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
			'id'   => $campaign->getId(),
			'name' => $campaign->getName(),
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

		/** @var GoogleAdsClient $client */
		$client   = $this->container->get( GoogleAdsClient::class );
		$response = $client->getCampaignBudgetServiceClient()->mutateCampaignBudgets(
			$this->get_id(),
			[ $operation ],
			$this->get_args()
		);

		/** @var CampaignBudget $created_budget */
		$created_budget = $response->getResults()[0];
		return $created_budget->getResourceName();
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
	 * @param string $name Resource name containing ID number.
	 *
	 * @return int
	 * @throws Exception When unable to parse resource ID.
	 */
	protected function parse_id( string $name ): int {
		if ( ! preg_match( '/[a-z]+\/([0-9]+)/', $name, $matches ) || empty( $matches[1] ) ) {
			throw new Exception( 'Invalid resource ID' );
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
}
