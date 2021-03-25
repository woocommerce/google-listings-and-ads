<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V6\Common\TagSnippet;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelSubTypeEnum\AdvertisingChannelSubType;
use Google\Ads\GoogleAds\V6\Enums\ConversionActionCategoryEnum\ConversionActionCategory;
use Google\Ads\GoogleAds\V6\Enums\ConversionActionStatusEnum\ConversionActionStatus;
use Google\Ads\GoogleAds\V6\Enums\ConversionActionTypeEnum\ConversionActionType;
use Google\Ads\GoogleAds\V6\Enums\MerchantCenterLinkStatusEnum\MerchantCenterLinkStatus;
use Google\Ads\GoogleAds\V6\Enums\TrackingCodePageFormatEnum\TrackingCodePageFormat;
use Google\Ads\GoogleAds\V6\Enums\TrackingCodeTypeEnum\TrackingCodeType;
use Google\Ads\GoogleAds\V6\Resources\Campaign;
use Google\Ads\GoogleAds\V6\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V6\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V6\Resources\ConversionAction;
use Google\Ads\GoogleAds\V6\Resources\ConversionAction\ValueSettings;
use Google\Ads\GoogleAds\V6\Resources\MerchantCenterLink;
use Google\Ads\GoogleAds\V6\Services\ConversionActionOperation;
use Google\Ads\GoogleAds\V6\Services\ConversionActionServiceClient;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V6\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V6\Services\CampaignOperation;
use Google\Ads\GoogleAds\V6\Services\MerchantCenterLinkOperation;
use Google\Ads\GoogleAds\V6\Services\MutateCampaignResult;
use Google\Ads\GoogleAds\V6\Services\MutateCampaignBudgetResult;
use Google\Ads\GoogleAds\V6\Services\MutateConversionActionResult;
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
	use ApiExceptionTrait;
	use AdsCampaignTrait;

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
	 * Set the ID.
	 *
	 * @param int $id
	 */
	public function set_id( int $id ): void {
		$this->id = new PositiveInteger( $id );
	}

	/**
	 * Get billing status.
	 *
	 * @return string
	 */
	public function get_billing_status(): string {
		try {
			if ( ! $this->get_id() ) {
				return BillingSetupStatus::UNKNOWN;
			}

			$query    = $this->build_query( [ 'billing_setup.status' ], 'billing_setup' );
			$response = $this->query( $query );

			foreach ( $response->iterateAllElements() as $row ) {
				$billing_setup = $row->getBillingSetup();
				$status        = BillingSetupStatus::label( $billing_setup->getStatus() );
				return apply_filters( 'woocommerce_gla_ads_billing_setup_status', $status, $this->get_id() );
			}
		} catch ( ApiException $e ) {
			// Do not act upon error as we might not have permission to access this account yet.
			if ( 'PERMISSION_DENIED' !== $e->getStatus() ) {
				do_action( 'gla_ads_client_exception', $e, __METHOD__ );
			}
		}

		return apply_filters( 'woocommerce_gla_ads_billing_setup_status', BillingSetupStatus::UNKNOWN, $this->get_id() );
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
			[ $operation ]
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
			[ $operation ]
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

		return $client->getGoogleAdsServiceClient()->search( $this->get_id(), $query );
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
	 * Get the Merchant Center ID.
	 *
	 * @return int
	 */
	protected function get_merchant_id(): int {
		/** @var Options $options */
		$options = $this->container->get( OptionsInterface::class );
		return $options->get( OptionsInterface::MERCHANT_ID );
	}

	/**
	 * Accept a link from a merchant account.
	 *
	 * @param int $merchant_id Merchant Center account id.
	 * @throws Exception When a link is unavailable.
	 */
	public function accept_merchant_link( int $merchant_id ) {
		$link = $this->get_merchant_link( $merchant_id );

		if ( $link->getStatus() === MerchantCenterLinkStatus::ENABLED ) {
			return;
		}

		$link->setStatus( MerchantCenterLinkStatus::ENABLED );

		$operation = new MerchantCenterLinkOperation();
		$operation->setUpdate( $link );
		$operation->setUpdateMask( FieldMasks::allSetFieldsOf( $link ) );

		$client   = $this->container->get( GoogleAdsClient::class );
		$response = $client->getMerchantCenterLinkServiceClient()->mutateMerchantCenterLink(
			$this->get_id(),
			$operation
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
			$this->get_id()
		);

		foreach ( $response->getMerchantCenterLinks() as $link ) {
			if ( $merchant_id === absint( $link->getId() ) ) {
				return $link;
			}
		}

		throw new Exception( __( 'Merchant link is not available to accept', 'google-listings-and-ads' ) );
	}

	/**
	 * Create the 'Google Listings and Ads purchase action' conversion action.
	 *
	 * @return array An array with some conversion action details.
	 * @throws Exception If the conversion action can't be created or retrieved.
	 */
	public function create_conversion_action(): array {
		try {
			$unique = sprintf( '%04x', mt_rand( 0, 0xffff ) );

			/** @var GoogleAdsClient $client */
			$client = $this->container->get( GoogleAdsClient::class );

			$conversion_action_operation = new ConversionActionOperation();
			$conversion_action_operation->setCreate(
				new ConversionAction(
					[
						'name'           => apply_filters(
							'woocommerce_gla_conversion_action_name',
							sprintf(
								/* translators: %1 is a random 4-digit string */
								__( '[%1$s] Google Listings and Ads purchase action', 'google-listings-and-ads' ),
								$unique
							)
						),
						'category'       => ConversionActionCategory::PURCHASE,
						'type'           => ConversionActionType::WEBPAGE,
						'status'         => ConversionActionStatus::ENABLED,
						'value_settings' => new ValueSettings(
							[
								'default_value'            => 0,
								'always_use_default_value' => false,
							]
						),
					]
				)
			);

			// Create the conversion.
			$response = $client->getConversionActionServiceClient()->mutateConversionActions(
				$this->get_id(),
				[ $conversion_action_operation ]
			);

			/** @var MutateConversionActionResult $added_conversion_action */
			$added_conversion_action = $response->getResults()->offsetGet( 0 );
			return $this->get_conversion_action( $added_conversion_action->getResourceName() );

		} catch ( Exception $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );
			$message = $e->getMessage();
			if ( $e instanceof ApiException ) {

				if ( $this->has_api_exception_error( $e, 'DUPLICATE_NAME' ) ) {
					$message = __( 'A conversion action with this name already exists', 'google-listings-and-ads' );
				} else {
					$message = $e->getBasicMessage();
				}
			}

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error creating conversion action: %s', 'google-listings-and-ads' ), $message ) );
		}
	}

	/**
	 * Retrieve a Conversion Action.
	 *
	 * @param string|int $resource_name The Conversion Action to retrieve (also accepts the Conversion Action ID).
	 *
	 * @return array An array with some conversion action details.
	 * @throws Exception If the Conversion Action can't be retrieved.
	 */
	public function get_conversion_action( $resource_name ): array {
		try {
			// Accept IDs too
			if ( is_numeric( $resource_name ) ) {
				$resource_name = ConversionActionServiceClient::conversionActionName( $this->get_id(), $resource_name );
			}

			/** @var ConversionActionServiceClient $ca_client */
			$ca_client         = $this->container->get( GoogleAdsClient::class )->getConversionActionServiceClient();
			$conversion_action = $ca_client->getConversionAction( $resource_name );

			return $this->convert_conversion_action( $conversion_action );
		} catch ( Exception $e ) {
			do_action( 'gla_ads_client_exception', $e, __METHOD__ );
			$message = $e->getMessage();
			if ( $e instanceof ApiException ) {
				$message = $e->getBasicMessage();
			}

			/* translators: %s Error message */
			throw new Exception( sprintf( __( 'Error retrieving conversion action: %s', 'google-listings-and-ads' ), $message ) );
		}
	}

	/**
	 * Convert conversion action data to an array.
	 *
	 * @param ConversionAction $conversion_action
	 *
	 * @return array An array with some conversion action details.
	 */
	private function convert_conversion_action( ConversionAction $conversion_action ): array {
		$return = [
			'id'     => $conversion_action->getId(),
			'name'   => $conversion_action->getName(),
			'status' => ConversionActionStatus::name( $conversion_action->getStatus() ),
		];
		foreach ( $conversion_action->getTagSnippets() as $t ) {
			/** @var TagSnippet $t */
			if ( $t->getType() !== TrackingCodeType::WEBPAGE ) {
				continue;
			}
			if ( $t->getPageFormat() !== TrackingCodePageFormat::HTML ) {
				continue;
			}
			preg_match( "#send_to': '([^/]+)/([^']+)'#", $t->getEventSnippet(), $matches );
			$return['conversion_id']    = $matches[1];
			$return['conversion_label'] = $matches[2];
			break;
		}
		return $return;
	}
}
