<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignCriterionQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignQuery;
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
use Google\Ads\GoogleAds\Util\V14\ResourceNames;
use Google\Ads\GoogleAds\V14\Common\MaximizeConversionValue;
use Google\Ads\GoogleAds\V14\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V14\Resources\Campaign;
use Google\Ads\GoogleAds\V14\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V14\Services\CampaignServiceClient;
use Google\Ads\GoogleAds\V14\Services\CampaignOperation;
use Google\Ads\GoogleAds\V14\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V14\Services\MutateOperation;
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
	 * AdsCampaign constructor.
	 *
	 * @param GoogleAdsClient      $client
	 * @param AdsCampaignBudget    $budget
	 * @param AdsCampaignCriterion $criterion
	 * @param GoogleHelper         $google_helper
	 */
	public function __construct( GoogleAdsClient $client, AdsCampaignBudget $budget, AdsCampaignCriterion $criterion, GoogleHelper $google_helper ) {
		$this->client        = $client;
		$this->budget        = $budget;
		$this->criterion     = $criterion;
		$this->google_helper = $google_helper;
	}

	/**
	 * Returns a list of campaigns with targeted locations retrieved from campaign criterion.
	 *
	 * @param bool $exclude_removed Exclude removed campaigns (default true).
	 * @param bool $fetch_criterion Combine the campaign data with criterion data (default true).
	 *
	 * @return array
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_campaigns( bool $exclude_removed = true, bool $fetch_criterion = true ): array {
		try {
			$query = ( new AdsCampaignQuery() )->set_client( $this->client, $this->options->get_ads_id() );

			if ( $exclude_removed ) {
				$query->where( 'campaign.status', 'REMOVED', '!=' );
			}

			$campaign_count      = 0;
			$campaign_results    = $query->get_results();
			$converted_campaigns = [];

			foreach ( $campaign_results->iterateAllElements() as $row ) {
				++$campaign_count;
				$campaign                               = $this->convert_campaign( $row );
				$converted_campaigns[ $campaign['id'] ] = $campaign;
			}

			if ( $exclude_removed ) {
				// Cache campaign count.
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
			foreach ( $campaign_results->iterateAllElements() as $row ) {
				$campaign                               = $this->convert_campaign( $row );
				$converted_campaigns[ $campaign['id'] ] = $campaign;
				break;
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

			// Operations must be in a specific order to match the temporary ID's.
			$operations = array_merge(
				[ $this->budget->create_operation( $params['name'], $params['amount'] ) ],
				[ $this->create_operation( $params['name'], $base_country ) ],
				$this->container->get( AdsAssetGroup::class )->create_operations(
					$this->temporary_resource_name(),
					$params['name']
				),
				$this->criterion->create_operations(
					$this->temporary_resource_name(),
					$location_ids
				)
			);

			$campaign_id = $this->mutate( $operations );

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
			'id'                 => $campaign->getId(),
			'name'               => $campaign->getName(),
			'status'             => CampaignStatus::label( $campaign->getStatus() ),
			'type'               => CampaignType::label( $campaign->getAdvertisingChannelType() ),
			'targeted_locations' => [],
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
