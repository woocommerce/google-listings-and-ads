<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
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
			$response = $this->query( 'SELECT campaign.id, campaign.name, campaign.shopping_setting.sales_country, campaign_budget.amount_micros FROM campaign' );

			foreach ( $response->iterateAllElements() as $row ) {
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

				$return[] = $data;
			}

			return $return;
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			$error = json_decode( $e->getMessage(), true );
			throw new Exception( sprintf( 'Error retrieving campaigns: %s', $error['message'] ) );
		}
	}

	/**
	 * Create a new campaign.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return string
	 * @throws Exception When an ApiException is caught.
	 */
	public function create_campaign( array $params ): string {
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

			return $created_campaign->getResourceName();
		} catch ( ApiException $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );

			$error = json_decode( $e->getMessage(), true );
			throw new Exception( sprintf( 'Error creating campaign: %s', $error['message'] ) );
		}
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
}
