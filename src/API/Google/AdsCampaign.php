<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignCriterionQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignAssetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAssetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V22\ResourceNames;
use Google\Ads\GoogleAds\V22\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V22\Enums\AssetTypeEnum\AssetType as AdsAssetType;
use Google\Ads\GoogleAds\V22\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V22\Resources\Campaign;
use Google\Ads\GoogleAds\V22\Enums\EuPoliticalAdvertisingStatusEnum\EuPoliticalAdvertisingStatus;
use Google\Ads\GoogleAds\V22\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V22\Services\Client\CampaignServiceClient;
use Google\Ads\GoogleAds\V22\Services\CampaignOperation;
use Google\Ads\GoogleAds\V22\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V22\Services\MutateGoogleAdsRequest;
use Google\Ads\GoogleAds\V22\Services\MutateOperation;
use Google\Ads\GoogleAds\V22\Resources\Campaign\AssetAutomationSetting;
use Google\Ads\GoogleAds\V22\Enums\AssetAutomationTypeEnum\AssetAutomationType;
use Google\Ads\GoogleAds\V22\Enums\AssetAutomationStatusEnum\AssetAutomationStatus;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Exception;

/**
 * Class AdsCampaign (Performance Max Campaign)
 * https://developers.google.com/google-ads/api/docs/performance-max/overview
 *
 * ContainerAware used for:
 * - AdsAssetGroup
 * - TransientsInterface
 * - WC
 *
 * @since 1.12.2 Refactored to support PMax and (legacy) SSC.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsCampaign implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
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
	 * @var AdsCampaignCriterion $criterion
	 */
	protected $criterion;

	/**
	 * @var GoogleHelper $google_helper
	 */
	protected $google_helper;

	/**
	 * @var AdsCampaignLabel $campaign_label
	 */
	protected $campaign_label;

	/**
	 * @var AdsCampaignAsset $campaign_asset
	 */
	protected $campaign_asset;

	/**
	 * AdsCampaign constructor.
	 *
	 * @param GoogleAdsClient      $client
	 * @param AdsCampaignBudget    $budget
	 * @param AdsCampaignCriterion $criterion
	 * @param GoogleHelper         $google_helper
	 * @param AdsCampaignLabel     $campaign_label
	 * @param AdsCampaignAsset     $campaign_asset
	 */
	public function __construct( GoogleAdsClient $client, AdsCampaignBudget $budget, AdsCampaignCriterion $criterion, GoogleHelper $google_helper, AdsCampaignLabel $campaign_label, AdsCampaignAsset $campaign_asset ) {
		$this->client         = $client;
		$this->budget         = $budget;
		$this->criterion      = $criterion;
		$this->google_helper  = $google_helper;
		$this->campaign_label = $campaign_label;
		$this->campaign_asset = $campaign_asset;
	}

	/**
	 * Returns a list of campaigns with targeted locations retrieved from campaign criterion.
	 *
	 * @param bool  $exclude_removed Exclude removed campaigns (default true).
	 * @param bool  $fetch_criterion Combine the campaign data with criterion data (default true).
	 * @param array $args Arguments for fetching campaigns, for example: per_page for limiting the number of results.
	 *
	 * @return array
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_campaigns( bool $exclude_removed = true, bool $fetch_criterion = true, array $args = [] ): array {
		try {
			$query = ( new AdsCampaignQuery() )->set_client( $this->client, $this->options->get_ads_id() );

			if ( $exclude_removed ) {
				$query->where( 'campaign.status', 'REMOVED', '!=' );
			}

			$count               = 0;
			$campaign_results    = $query->get_results();
			$converted_campaigns = [];

			foreach ( $campaign_results->iterateAllElements() as $row ) {
				++$count;
				$campaign                               = $this->convert_campaign( $row );
				$converted_campaigns[ $campaign['id'] ] = $campaign;

				// Break early if we request a limited result.
				if ( ! empty( $args['per_page'] ) && $count >= $args['per_page'] ) {
					break;
				}
			}

			if ( $exclude_removed ) {
				// Cache campaign count.
				$campaign_count = $campaign_results->getPage()->getResponseObject()->getTotalResultsCount();
				$this->container->get( TransientsInterface::class )->set(
					TransientsInterface::ADS_CAMPAIGN_COUNT,
					$campaign_count,
					HOUR_IN_SECONDS * 12
				);
			}

			if ( $fetch_criterion ) {
				$converted_campaigns = $this->combine_campaigns_and_campaign_criterion_results( $converted_campaigns );
			}

			return array_values( $converted_campaigns );
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );
			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving campaigns: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Retrieve a single campaign with targeted locations retrieved from campaign criterion.
	 *
	 * @param int $id Campaign ID.
	 *
	 * @return array
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_campaign( int $id ): array {
		try {
			$campaign_results = ( new AdsCampaignQuery() )->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'campaign.id', $id, '=' )
				->get_results();

			$converted_campaigns = [];

			// Get only the first element from campaign results
			$elements = $campaign_results->iterateAllElements();
			if ( $elements !== null ) {
				foreach ( $elements as $row ) {
					$campaign                               = $this->convert_campaign( $row );
					$converted_campaigns[ $campaign['id'] ] = $campaign;
					break;
				}
			}

			if ( ! empty( $converted_campaigns ) ) {
				$combined_results = $this->combine_campaigns_and_campaign_criterion_results( $converted_campaigns );
				return reset( $combined_results );
			}

			return [];
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );
			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving campaign: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[
					'errors' => $errors,
					'id'     => $id,
				]
			);
		}
	}

	/**
	 * Create a new campaign.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function create_campaign( array $params ): array {
		try {
			$base_country = $this->container->get( WC::class )->get_base_country();

			$location_ids = array_map(
				function ( $country_code ) {
					return $this->google_helper->find_country_id_by_code( $country_code );
				},
				$params['targeted_locations']
			);

			$location_ids = array_filter( $location_ids );

			// Create budget operations.
			$budget_operations = [ $this->budget->create_operation( $params['name'], $params['amount'] ) ];

			// Create campaign operations.
			$campaign_operations = [ $this->create_operation( $params['name'], $base_country, $params['eu_political_advertising_confirmation'] ) ];

			// Create asset group operations.
			$ad_asset_group = $this->container->get( AdsAssetGroup::class );

			// If final URL and assets are passed create operations for those.
			if ( isset( $params['final_url'] ) && isset( $params['assets'] ) ) {
				$asset_group_operations = $ad_asset_group->create_operations_with_assets(
					$this->temporary_resource_name(),
					$params['name'],
					$params['final_url'],
					$params['assets']
				);
			} else {
				// Create "empty" asset group operations.
				$asset_group_operations = $ad_asset_group->create_operations(
					$this->temporary_resource_name(),
					$params['name']
				);
			}

			// Location/Targeting criteria operations.
			$criteria_operations = $this->criterion->create_operations(
				$this->temporary_resource_name(),
				$location_ids
			);

			// Operations must be in a specific order to match the temporary ID's.
			$operations = array_merge(
				$budget_operations,
				$campaign_operations,
				$asset_group_operations,
				$criteria_operations
			);

			$campaign_id = $this->mutate( $operations );

			if ( isset( $params['label'] ) ) {
				$this->campaign_label->assign_label_to_campaign_by_label_name( $campaign_id, $params['label'] );
			}

			// Clear cached campaign count.
			$this->container->get( TransientsInterface::class )->delete( TransientsInterface::ADS_CAMPAIGN_COUNT );

			return [
				'id'      => $campaign_id,
				'status'  => CampaignStatus::ENABLED,
				'type'    => CampaignType::PERFORMANCE_MAX,
				'country' => $base_country,
			] + $params;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );
			/* translators: %s Error message */
			$message = sprintf( __( 'Error creating campaign: %s', 'google-listings-and-ads' ), reset( $errors ) );

			if ( isset( $errors['DUPLICATE_CAMPAIGN_NAME'] ) ) {
				$message = __( 'A campaign with this name already exists', 'google-listings-and-ads' );
			}

			throw new ExceptionWithResponseData(
				$message,
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[ 'errors' => $errors ]
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
	 * @throws ExceptionWithResponseData When an ApiException is caught.
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

			if ( isset( $params['eu_political_advertising_confirmation'] ) && true === $params['eu_political_advertising_confirmation'] ) {
				$campaign_fields['contains_eu_political_advertising'] = EuPoliticalAdvertisingStatus::CONTAINS_EU_POLITICAL_ADVERTISING;
			} else {
				$campaign_fields['contains_eu_political_advertising'] = EuPoliticalAdvertisingStatus::DOES_NOT_CONTAIN_EU_POLITICAL_ADVERTISING;
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

			$errors = $this->get_exception_errors( $e );
			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Error editing campaign: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[
					'errors' => $errors,
					'id'     => $campaign_id,
				]
			);
		}
	}

	/**
	 * Delete a campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 *
	 * @return int
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function delete_campaign( int $campaign_id ): int {
		try {
			$campaign_resource_name = ResourceNames::forCampaign( $this->options->get_ads_id(), $campaign_id );

			$operations = [
				$this->delete_operation( $campaign_resource_name ),
			];

			// Clear cached campaign count.
			$this->container->get( TransientsInterface::class )->delete( TransientsInterface::ADS_CAMPAIGN_COUNT );

			return $this->mutate( $operations );
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );
			/* translators: %s Error message */
			$message = sprintf( __( 'Error deleting campaign: %s', 'google-listings-and-ads' ), reset( $errors ) );

			if ( isset( $errors['OPERATION_NOT_PERMITTED_FOR_REMOVED_RESOURCE'] ) ) {
				$message = __( 'This campaign has already been deleted', 'google-listings-and-ads' );
			}

			throw new ExceptionWithResponseData(
				$message,
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[
					'errors' => $errors,
					'id'     => $campaign_id,
				]
			);
		}
	}

	/**
	 * Retrieve the enabled campaign with the highest spend amount.
	 *
	 * @return array
	 */
	public function get_highest_spend_campaign(): array {
		try {
			$campaigns = $this->get_campaigns();
		} catch ( Exception $e ) {
			return [];
		}

		return array_reduce(
			$campaigns,
			function ( $highest, $campaign ) {
				if ( CampaignStatus::ENABLED === $campaign['status'] && ( empty( $highest ) || $campaign['amount'] > $highest['amount'] ) ) {
					return $campaign;
				}

				return $highest;
			},
			[]
		);
	}

	/**
	 * Retrieves the status of converting campaigns.
	 * The status is cached for an hour during unconverted.
	 *
	 * - unconverted    - Still need to convert some older campaigns
	 * - converted      - All campaigns are converted to PMax campaigns
	 * - not-applicable - User never had any older campaign types
	 *
	 * @since 2.0.3
	 *
	 * @return string
	 */
	public function get_campaign_convert_status(): string {
		$convert_status = $this->options->get( OptionsInterface::CAMPAIGN_CONVERT_STATUS );

		if ( ! is_array( $convert_status ) || empty( $convert_status['status'] ) ) {
			$convert_status = [ 'status' => 'unknown' ];
		}

		// Refetch if status is unconverted and older than an hour.
		if (
			in_array( $convert_status['status'], [ 'unconverted', 'unknown' ], true ) &&
			( empty( $convert_status['updated'] ) || time() - $convert_status['updated'] > HOUR_IN_SECONDS )
		) {
			$old_campaigns            = 0;
			$old_removed_campaigns    = 0;
			$convert_status['status'] = 'unconverted';

			try {
				foreach ( $this->get_campaigns( false, false ) as $campaign ) {
					if ( CampaignType::PERFORMANCE_MAX !== $campaign['type'] ) {
						if ( CampaignStatus::REMOVED === $campaign['status'] ) {
							++$old_removed_campaigns;
						} else {
							++$old_campaigns;
						}
					}
				}

				// No old campaign types means we don't need to convert.
				if ( ! $old_removed_campaigns && ! $old_campaigns ) {
					$convert_status['status'] = 'not-applicable';
				}

				// All old campaign types have been removed, means we converted.
				if ( ! $old_campaigns && $old_removed_campaigns > 0 ) {
					$convert_status['status'] = 'converted';
				}
			} catch ( Exception $e ) {
				// Error when retrieving campaigns, do not handle conversion.
				$convert_status['status'] = 'unknown';
			}

			$convert_status['updated'] = time();
			$this->options->update( OptionsInterface::CAMPAIGN_CONVERT_STATUS, $convert_status );
		}

		return $convert_status['status'];
	}

	/**
	 * Return a temporary resource name for the campaign.
	 *
	 * @return string
	 */
	protected function temporary_resource_name() {
		return ResourceNames::forCampaign( $this->options->get_ads_id(), self::TEMPORARY_ID );
	}

	/**
	 * Returns a campaign create operation.
	 *
	 * @param string      $campaign_name
	 * @param string|null $country
	 * @param bool        $is_eu_political
	 *
	 * @return MutateOperation
	 */
	protected function create_operation( string $campaign_name, ?string $country, bool $is_eu_political ): MutateOperation {
		$merchant_id   = $this->options->get_merchant_id();
		$campaign_data = [
			'resource_name'                     => $this->temporary_resource_name(),
			'name'                              => $campaign_name,
			'advertising_channel_type'          => AdvertisingChannelType::PERFORMANCE_MAX,
			'status'                            => CampaignStatus::number( 'enabled' ),
			'campaign_budget'                   => $this->budget->temporary_resource_name(),
			'maximize_conversion_value'         => new MaximizeConversionValue(),
			'asset_automation_settings'         => [
				new AssetAutomationSetting(
					[
						'asset_automation_type'   => AssetAutomationType::FINAL_URL_EXPANSION_TEXT_ASSET_AUTOMATION,
						'asset_automation_status' => AssetAutomationStatus::OPTED_IN,
					]
				),
			],
			'contains_eu_political_advertising' => $is_eu_political ? EuPoliticalAdvertisingStatus::CONTAINS_EU_POLITICAL_ADVERTISING : EuPoliticalAdvertisingStatus::DOES_NOT_CONTAIN_EU_POLITICAL_ADVERTISING,
		];

		// Only include shopping_setting if Merchant Center account is connected.
		if ( $merchant_id > 0 && $country !== null ) {
			$campaign_data['shopping_setting'] = new ShoppingSetting(
				[
					'merchant_id' => $merchant_id,
					'feed_label'  => $country,
				]
			);
		} else {
			// Turn off brand guidelines for non-shopping campaigns.
			$campaign_data['brand_guidelines_enabled'] = false;
		}

		$campaign = new Campaign( $campaign_data );

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
			'id'                 => $campaign->getId(),
			'name'               => $campaign->getName(),
			'status'             => CampaignStatus::label( $campaign->getStatus() ),
			'type'               => CampaignType::label( $campaign->getAdvertisingChannelType() ),
			'targeted_locations' => [],
		];

		$eu_political_enum = $campaign->getContainsEuPoliticalAdvertising();

		$data += [
			'eu_political_advertising_confirmation' => EuPoliticalAdvertisingStatus::CONTAINS_EU_POLITICAL_ADVERTISING === $eu_political_enum ? true : false,
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
				'country' => $shopping->getFeedLabel(),
			];
		}

		return $data;
	}

	/**
	 * Combine converted campaigns data with campaign criterion results data
	 *
	 * @param array $campaigns Campaigns data returned from a query request and converted by convert_campaign function.
	 *
	 * @return array
	 */
	protected function combine_campaigns_and_campaign_criterion_results( array $campaigns ): array {
		if ( empty( $campaigns ) ) {
			return [];
		}

		$campaign_criterion_results = ( new AdsCampaignCriterionQuery() )->set_client( $this->client, $this->options->get_ads_id() )
			->where( 'campaign.id', array_keys( $campaigns ), 'IN' )
			// negative: Whether to target (false) or exclude (true) the criterion.
			->where( 'campaign_criterion.negative', 'false', '=' )
			->where( 'campaign_criterion.status', 'REMOVED', '!=' )
			->where( 'campaign_criterion.location.geo_target_constant', '', 'IS NOT NULL' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $campaign_criterion_results->iterateAllElements() as $row ) {
			$campaign    = $row->getCampaign();
			$campaign_id = $campaign->getId();

			if ( ! isset( $campaigns[ $campaign_id ] ) ) {
				continue;
			}

			$campaign_criterion  = $row->getCampaignCriterion();
			$location            = $campaign_criterion->getLocation();
			$geo_target_constant = $location->getGeoTargetConstant();
			$location_id         = $this->parse_geo_target_location_id( $geo_target_constant );
			$country_code        = $this->google_helper->find_country_code_by_id( $location_id );

			if ( $country_code ) {
				$campaigns[ $campaign_id ]['targeted_locations'][] = $country_code;
			}
		}

		return $campaigns;
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
		$request = new MutateGoogleAdsRequest();
		$request->setCustomerId( $this->options->get_ads_id() );
		$request->setMutateOperations( $operations );
		$responses = $this->client->getGoogleAdsServiceClient()->mutate( $request );
		foreach ( $responses->getMutateOperationResponses() ?? [] as $response ) {
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

	/**
	 * Build campaign asset link operations for Brand Guidelines.
	 *
	 * Derives business name and logo asset IDs from the provided $assets array (existing assets)
	 * and $assets_for_creation + $created_asset_resource_names (newly created assets). If no assets are
	 * provided, discovers brand assets from campaign/account/asset group.
	 *
	 * @param int   $campaign_id                        Campaign ID.
	 * @param array $assets                             Optional. The full assets array from the edit payload.
	 * @param array $assets_for_creation                Optional. Assets that were created (same order as $created_asset_resource_names).
	 * @param array $created_asset_resource_names Optional. Asset resource names from AdsAsset::create_assets() mutate (same order as $assets_for_creation).
	 * @return MutateOperation[]
	 */
	public function get_brand_asset_link_operations( int $campaign_id, array $assets = [], array $assets_for_creation = [], array $created_asset_resource_names = [] ): array {
		try {
			// Query existing campaign-level brand assets (for replace semantics and limit checks).
			$campaign_assets = ( new AdsCampaignAssetQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'campaign.id', $campaign_id, '=' )
				->where( 'campaign_asset.status', 'REMOVED', '!=' )
				->get_results();

			$has_business_name               = false;
			$has_logo                        = false;
			$existing_business_name_resource = null;
			$existing_logo_resource          = null;

			foreach ( $campaign_assets->iterateAllElements() as $row ) {
				$campaign_asset = $row->getCampaignAsset();
				if ( ! $campaign_asset ) {
					continue;
				}

				$field_type = AssetFieldType::label( $campaign_asset->getFieldType() );

				if ( AssetFieldType::BUSINESS_NAME === $field_type ) {
					$has_business_name               = true;
					$existing_business_name_resource = $campaign_asset->getResourceName();
				}

				if ( AssetFieldType::LOGO === $field_type ) {
					$has_logo               = true;
					$existing_logo_resource = $campaign_asset->getResourceName();
				}
			}

			// Derive brand asset IDs from the provided assets data.
			$business_name_ids = [];
			$logo_ids          = [];

			if ( ! empty( $assets ) || ! empty( $assets_for_creation ) ) {
				// Extract IDs from existing unchanged assets (assets with 'id' but no 'content' key).
				// Note: Deletion operations have 'content' => null, so we use array_key_exists to check key presence, not value.
				foreach ( $assets as $asset ) {
					if ( ! empty( $asset['id'] ) && ! array_key_exists( 'content', $asset ) && isset( $asset['field_type'] ) ) {
						if ( 'business_name' === $asset['field_type'] ) {
							$business_name_ids[] = (int) $asset['id'];
						} elseif ( 'logo' === $asset['field_type'] ) {
							$logo_ids[] = (int) $asset['id'];
						}
					}
				}

				// Extract IDs from newly created assets by matching assets_for_creation to created_asset_resource_names.
				$total_created = count( $assets_for_creation );
				for ( $i = 0; $i < $total_created; $i++ ) {
					if ( empty( $created_asset_resource_names[ $i ] ) ) {
						continue;
					}
					$field_type = $assets_for_creation[ $i ]['field_type'] ?? '';
					if ( 'business_name' === $field_type || 'logo' === $field_type ) {
						$asset_id = $this->parse_asset_id_from_resource_name( $created_asset_resource_names[ $i ] );
						if ( $asset_id !== null ) {
							if ( 'business_name' === $field_type ) {
								$business_name_ids[] = $asset_id;
							} else {
								$logo_ids[] = $asset_id;
							}
						}
					}
				}
			}

			// When brand asset IDs were derived from the edit payload: replace existing links so changes persist.
			if ( ! empty( $business_name_ids ) || ! empty( $logo_ids ) ) {
				$operations = [];
				if ( ! empty( $business_name_ids ) && $existing_business_name_resource !== null ) {
					$operations[] = $this->campaign_asset->create_remove_operation( $existing_business_name_resource );
				}
				if ( ! empty( $logo_ids ) && $existing_logo_resource !== null ) {
					$operations[] = $this->campaign_asset->create_remove_operation( $existing_logo_resource );
				}
				$ids_to_link_business = ! empty( $business_name_ids ) ? [ $business_name_ids[0] ] : [];
				$ids_to_link_logo     = ! empty( $logo_ids ) ? [ $logo_ids[0] ] : [];
				$link_ops             = $this->campaign_asset->create_link_operations( $campaign_id, $ids_to_link_business, $ids_to_link_logo );
				return array_merge( $operations, $link_ops );
			}

			// Discovery path (no IDs provided): only link what's missing to avoid exceeding limit.
			if ( $has_business_name && $has_logo ) {
				return [];
			}

			$needs_business_name = ! $has_business_name;
			$needs_logo          = ! $has_logo;

			if ( ! $needs_business_name && ! $needs_logo ) {
				return [];
			}

			// Query for account-level assets: TEXT assets for business name, IMAGE assets for logos
			// Use string enum names in the WHERE clause, not numeric values
			$account_assets = ( new AdsAssetQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'asset.type', [ 'TEXT', 'IMAGE' ], 'IN' )
				->where( 'asset.status', 'REMOVED', '!=' )
				->get_results();

			$business_ids = [];
			$logo_ids     = [];

			foreach ( $account_assets->iterateAllElements() as $row ) {
				$asset = $row->getAsset();
				if ( ! $asset ) {
					continue;
				}

				$type = AdsAssetType::name( $asset->getType() );

				// TEXT assets can be business name (prefer if available)
				if ( $needs_business_name && 'TEXT' === $type && empty( $business_ids ) ) {
					$business_ids[] = $asset->getId();
				}

				// IMAGE assets are logos
				if ( $needs_logo && 'IMAGE' === $type ) {
					$logo_ids[] = $asset->getId();
				}

				if ( ( ! $needs_business_name || ! empty( $business_ids ) ) && ( ! $needs_logo || ! empty( $logo_ids ) ) ) {
					break;
				}
			}

			// If we couldn't find the required assets at account level, we cannot proceed further.
			// Note: We avoid querying asset groups here to prevent circular dependency (AdsAssetGroup depends on AdsCampaign).
			// The caller should provide assets via the $assets parameter for proper brand asset linking.

			if ( empty( $business_ids ) && empty( $logo_ids ) ) {
				return [];
			}

			return $this->campaign_asset->create_link_operations( $campaign_id, $business_ids, $logo_ids );
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
			return [];
		}
	}

	/**
	 * Get campaign-level business name and logo assets for display (when Brand Guidelines is enabled).
	 * Used to populate the asset group edit form since these assets are linked at campaign level, not asset group level.
	 *
	 * @param int $campaign_id Campaign ID.
	 * @return array{business_name: array{id: int, content: string}|null, logo: array<array{id: int, content: string}>}
	 */
	public function get_campaign_brand_assets_for_display( int $campaign_id ): array {
		$result = [
			'business_name' => null,
			'logo'          => [],
		];
		try {
			$asset_columns = [
				'asset.id',
				'asset.type',
				'asset.text_asset.text',
				'asset.image_asset.full_size.url',
				'asset.name',
			];
			$results       = ( new AdsCampaignAssetQuery() )
				->add_columns( $asset_columns )
				->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'campaign.id', $campaign_id, '=' )
				->where( 'campaign_asset.field_type', [ AssetFieldType::name( AssetFieldType::BUSINESS_NAME ), AssetFieldType::name( AssetFieldType::LOGO ) ], 'IN' )
				->where( 'campaign_asset.status', 'REMOVED', '!=' )
				->get_results();

			$asset = $this->container->get( AdsAsset::class );
			foreach ( $results->iterateAllElements() as $row ) {
				$campaign_asset = $row->getCampaignAsset();
				if ( ! $campaign_asset || ! $row->getAsset() ) {
					continue;
				}
				$field_type = AssetFieldType::label( $campaign_asset->getFieldType() );
				$converted  = $asset->convert_asset( $row );
				if ( AssetFieldType::BUSINESS_NAME === $field_type ) {
					$result['business_name'] = $converted;
				}
				if ( AssetFieldType::LOGO === $field_type ) {
					$result['logo'][] = $converted;
				}
			}
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}
		return $result;
	}

	/**
	 * Parse asset ID from resource name.
	 *
	 * @param string $resource_name Resource name containing ID.
	 * @return int|null
	 */
	protected function parse_asset_id_from_resource_name( string $resource_name ): ?int {
		$parts = explode( '/', $resource_name );
		return ! empty( $parts ) ? absint( end( $parts ) ) : null;
	}

	/**
	 * Convert location ID from a geo target constant resource name to an int.
	 *
	 * @param string $geo_target_constant Resource name containing ID number.
	 *
	 * @return int
	 * @throws Exception When unable to parse resource ID.
	 */
	protected function parse_geo_target_location_id( string $geo_target_constant ): int {
		if ( 1 === preg_match( '#geoTargetConstants/(?<id>\d+)#', $geo_target_constant, $parts ) ) {
			return absint( $parts['id'] );
		} else {
			throw new Exception( __( 'Invalid geo target location ID', 'google-listings-and-ads' ) );
		}
	}
}
