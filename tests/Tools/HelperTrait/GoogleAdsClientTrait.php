<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CallToActionType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Exception;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;
use Google\Ads\GoogleAds\V11\Common\LocationInfo;
use Google\Ads\GoogleAds\V11\Common\Metrics;
use Google\Ads\GoogleAds\V11\Common\Segments;
use Google\Ads\GoogleAds\V11\Common\TagSnippet;
use Google\Ads\GoogleAds\V11\Common\ImageAsset;
use Google\Ads\GoogleAds\V11\Common\TextAsset;
use Google\Ads\GoogleAds\V11\Common\CallToActionAsset;
use Google\Ads\GoogleAds\V11\Common\ImageDimension;
use Google\Ads\GoogleAds\V11\Enums\AccessRoleEnum\AccessRole;
use Google\Ads\GoogleAds\V11\Enums\CampaignStatusEnum\CampaignStatus as AdsCampaignStatus;
use Google\Ads\GoogleAds\V11\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType as AdsCampaignType;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V11\Enums\TrackingCodePageFormatEnum\TrackingCodePageFormat;
use Google\Ads\GoogleAds\V11\Enums\TrackingCodeTypeEnum\TrackingCodeType;
use Google\Ads\GoogleAds\V11\Resources\BillingSetup;
use Google\Ads\GoogleAds\V11\Resources\Campaign;
use Google\Ads\GoogleAds\V11\Resources\Asset;
use Google\Ads\GoogleAds\V11\Resources\AssetGroup;
use Google\Ads\GoogleAds\V11\Resources\AssetGroupAsset;
use Google\Ads\GoogleAds\V11\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V11\Resources\CampaignCriterion;
use Google\Ads\GoogleAds\V11\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V11\Resources\ConversionAction;
use Google\Ads\GoogleAds\V11\Resources\Customer;
use Google\Ads\GoogleAds\V11\Resources\CustomerUserAccess;
use Google\Ads\GoogleAds\V11\Resources\MerchantCenterLink;
use Google\Ads\GoogleAds\V11\Resources\ShoppingPerformanceView;
use Google\Ads\GoogleAds\V11\Services\ConversionActionServiceClient;
use Google\Ads\GoogleAds\V11\Services\CustomerServiceClient;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V11\Services\ListAccessibleCustomersResponse;
use Google\Ads\GoogleAds\V11\Services\ListMerchantCenterLinksResponse;
use Google\Ads\GoogleAds\V11\Services\MerchantCenterLinkServiceClient;
use Google\Ads\GoogleAds\V11\Services\MutateCampaignResult;
use Google\Ads\GoogleAds\V11\Services\MutateConversionActionResult;
use Google\Ads\GoogleAds\V11\Services\MutateConversionActionsResponse;
use Google\Ads\GoogleAds\V11\Services\MutateGoogleAdsResponse;
use Google\Ads\GoogleAds\V11\Services\MutateOperationResponse;
use Google\Ads\GoogleAds\V11\Services\MutateOperation;
use Google\Ads\GoogleAds\V11\Services\AssetOperation;
use Google\Ads\GoogleAds\V11\Services\MutateAssetGroupResult;
use Google\Ads\GoogleAds\V11\Services\MutateAssetResult;
use Google\ApiCore\ApiException;
use Google\ApiCore\Page;
use Google\ApiCore\PagedListResponse;

/**
 * Trait GoogleAdsClient
 *
 * @property int                                      $ads_id
 * @property MockObject|ConversionActionServiceClient $conversion_action_service
 * @property MockObject|CustomerServiceClient         $customer_service
 * @property MockObject|GoogleAdsClient               $client
 * @property MockObject|GoogleAdsServiceClient        $service_client
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait GoogleAdsClientTrait {

	use MicroTrait;

	protected $client;
	protected $service_client;

	/**
	 * Generate a mocked GoogleAdsClient.
	 */
	protected function ads_client_setup() {
		$this->ads_id = 12345;

		$this->service_client = $this->createMock( GoogleAdsServiceClient::class );
		$this->client         = $this->createMock( GoogleAdsClient::class );
		$this->client->method( 'getGoogleAdsServiceClient' )->willReturn( $this->service_client );

		$this->customer_service = $this->createMock( CustomerServiceClient::class );
		$this->client->method( 'getCustomerServiceClient' )->willReturn( $this->customer_service );

		$this->conversion_action_service = $this->createMock( ConversionActionServiceClient::class );
		$this->client->method( 'getConversionActionServiceClient' )->willReturn( $this->conversion_action_service );
	}

	/**
	 * Generates a mocked exception when an campaign is mutated.
	 *
	 * @param ApiException $exception
	 */
	protected function generate_campaign_mutate_mock_exception( ApiException $exception ) {
		$this->service_client->method( 'mutate' )->willThrowException( $exception );
	}

	/**
	 * Generate a mocked mutate campaign response.
	 * Asserts that set of operations contains an operation with the expected type.
	 *
	 * @param string $type         Mutation type we are expecting (create/update/remove).
	 * @param int    $campaign_id  Campaign ID we expect to see in the mutate result.
	 */
	protected function generate_campaign_mutate_mock( string $type, int $campaign_id ) {
		$campaign_result = $this->createMock( MutateCampaignResult::class );
		$campaign_result->method( 'getResourceName' )->willReturn(
			ResourceNames::forCampaign( $this->ads_id, $campaign_id )
		);

		$response = ( new MutateGoogleAdsResponse() )->setMutateOperationResponses(
			[
				( new MutateOperationResponse() )->setCampaignResult( $campaign_result ),
			]
		);

		$this->service_client->expects( $this->once() )
			->method( 'mutate' )
			->willReturnCallback(
				function( int $ads_id, array $operations ) use ( $type, $response ) {
					// Assert that the campaign operation is the right type.
					foreach ( $operations as $operation ) {
						if ( 'campaign_operation' === $operation->getOperation() ) {
							$operation = $operation->getCampaignOperation();
							$this->assertEquals( $type, $operation->getOperation() );
						}
					}

					return $response;
				}
			);
	}

	/**
	 * Generates a mocked exception when an AdsQuery run.
	 *
	 * @param ApiException $exception
	 */
	protected function generate_ads_query_mock_exception( ApiException $exception ) {
		$this->service_client->method( 'search' )->willThrowException( $exception );
	}

	/**
	 * Generates a mocked AdsQuery response with a list of mocked rows.
	 *
	 * @param GoogleAdsRow[] $rows
	 */
	protected function generate_ads_query_mock( array $rows ) {
		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->method( 'iterateAllElements' )->willReturn( $rows );

		$this->service_client->method( 'search' )->willReturn( $list_response );
	}

	/**
	 * Generates a mocked AdsQuery response with a list of mocked rows.
	 *
	 * @param array $responses List of responses containing sets of GoogleAdsRow.
	 */
	protected function generate_ads_multiple_query_mock( array $responses ) {
		foreach ( $responses as $key => $rows ) {
			$responses[ $key ] = $this->createMock( PagedListResponse::class );
			$responses[ $key ]->method( 'iterateAllElements' )->willReturn( $rows );
		}

		$this->service_client->method( 'search' )->willReturnOnConsecutiveCalls( ...$responses );
	}

	/**
	 * Generates mocked AdsCampaignQuery and AdsCampaignCriterionQuery responses.
	 *
	 * @param array $campaigns_responses Set of campaign data to convert.
	 * @param array $campaign_criterion_responses Set of campaign criterion data to convert.
	 */
	protected function generate_ads_campaign_query_mock( array $campaigns_responses, $campaign_criterion_responses ) {
		$campaigns_row_mock          = array_map( [ $this, 'generate_campaign_row_mock' ], $campaigns_responses );
		$campaign_criterion_row_mock = array_map( [ $this, 'generate_campaign_criterion_row_mock' ], $campaign_criterion_responses );

		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->method( 'iterateAllElements' )->willReturnOnConsecutiveCalls(
			$campaigns_row_mock,
			$campaign_criterion_row_mock
		);

		$this->service_client
			->method( 'search' )->willReturn( $list_response );
	}

	/**
	 * Generates a mocked empty campaigns response.
	 */
	protected function generate_ads_campaign_query_mock_with_no_campaigns() {
		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->method( 'iterateAllElements' )->willReturn( [] );

		// Method search() will only being called once by AdsCampaignQuery
		// since there were no campaigns returned by AdsCampaignQuery, it
		// won't be calling AdsCampaignCriterionQuery then.
		$this->service_client->expects( $this->once() )
			->method( 'search' )->willReturn( $list_response );
	}

	/**
	 * Generates a mocked AdsCampaignBudgetQuery response.
	 *
	 * @param int $budget_id
	 */
	protected function generate_ads_campaign_budget_query_mock( int $budget_id ) {
		$campaign = $this->createMock( Campaign::class );
		$campaign->method( 'getCampaignBudget' )->willReturn(
			$this->generate_campaign_budget_resource_name( $budget_id )
		);

		$this->generate_ads_query_mock(
			[
				( new GoogleAdsRow() )->setCampaign( $campaign ),
			]
		);
	}

	/**
	 * Generates a mocked AdsBillingStatusQuery response.
	 *
	 * @param int $status
	 */
	protected function generate_ads_billing_status_query_mock( int $status ) {
		$billing_setup = $this->createMock( BillingSetup::class );
		$billing_setup->method( 'getStatus' )->willReturn( $status );

		$this->generate_ads_query_mock(
			[
				( new GoogleAdsRow() )->setBillingSetup( $billing_setup ),
			]
		);
	}

	/**
	 * Generates a mocked AdsAccountAccessQuery response.
	 *
	 * @param bool $has_access
	 */
	protected function generate_ads_access_query_mock( bool $has_access ) {
		$access = $this->createMock( CustomerUserAccess::class );
		$access->method( 'getAccessRole' )->willReturn(
			$has_access ? AccessRole::ADMIN : AccessRole::UNKNOWN
		);

		$this->generate_ads_query_mock(
			[
				( new GoogleAdsRow() )->setCustomerUserAccess( $access ),
			]
		);
	}

	/**
	 * Converts campaign data to a mocked GoogleAdsRow.
	 *
	 * @param array $data Campaign data to convert.
	 *
	 * @return GoogleAdsRow
	 */
	protected function generate_campaign_row_mock( array $data ): GoogleAdsRow {
		$setting = $this->createMock( ShoppingSetting::class );
		$setting->method( 'getSalesCountry' )->willReturn( $data['country'] );

		$campaign = $this->createMock( Campaign::class );
		$campaign->method( 'getId' )->willReturn( $data['id'] );
		$campaign->method( 'getName' )->willReturn( $data['name'] );
		$campaign->method( 'getStatus' )->willReturn( CampaignStatus::number( $data['status'] ) );
		$campaign->method( 'getAdvertisingChannelType' )->willReturn( CampaignType::number( $data['type'] ) );
		$campaign->method( 'getShoppingSetting' )->willReturn( $setting );

		$budget = $this->createMock( CampaignBudget::class );
		$budget->method( 'getAmountMicros' )->willReturn( $this->to_micro( $data['amount'] ) );

		return ( new GoogleAdsRow() )
			->setCampaign( $campaign )
			->setCampaignBudget( $budget );
	}

	/**
	 * Converts campaign criterion data to a mocked GoogleAdsRow.
	 *
	 * @param array $data Campaign criterion data to convert.
	 *
	 * @return GoogleAdsRow
	 */
	protected function generate_campaign_criterion_row_mock( array $data ): GoogleAdsRow {
		$campaign = $this->createMock( Campaign::class );
		$campaign->method( 'getId' )->willReturn( $data['campaign_id'] );

		$location_info = $this->createMock( LocationInfo::class );
		$location_info->method( 'getGeoTargetConstant' )->willReturn( $data['geo_target_constant'] );

		$campaign_criterion = $this->createMock( CampaignCriterion::class );
		$campaign_criterion->method( 'getLocation' )->willReturn( $location_info );

		return ( new GoogleAdsRow() )
			->setCampaign( $campaign )
			->setCampaignCriterion( $campaign_criterion );
	}

	/**
	 * Generates a campaign resource name.
	 *
	 * @param int $campaign_id
	 */
	protected function generate_campaign_resource_name( int $campaign_id ) {
		return ResourceNames::forCampaign( $this->ads_id, $campaign_id );
	}

	/**
	 * Generates a campaign budget resource name.
	 *
	 * @param int $budget_id
	 */
	protected function generate_campaign_budget_resource_name( int $budget_id ) {
		return ResourceNames::forCampaignBudget( $this->ads_id, $budget_id );
	}

	/**
	 * Generates an ad group resource name.
	 *
	 * @param int $ad_group_id
	 */
	protected function generate_ad_group_resource_name( int $ad_group_id ) {
		return ResourceNames::forAdGroup( $this->ads_id, $ad_group_id );
	}

	/**
	 * Generates an asset group resource name.
	 *
	 * @param int $asset_group_id
	 */
	protected function generate_asset_group_resource_name( int $asset_group_id ) {
		return ResourceNames::forAssetGroup( $this->ads_id, $asset_group_id );
	}

	/**
	 * Generates an asset resource name.
	 *
	 * @param int $asset_id
	 */
	protected function generate_asset_resource_name( int $asset_id ) {
		return ResourceNames::forAsset( $this->ads_id, $asset_id );
	}

	/**
	 * Generates a list of mocked AdsAccountQuery responses.
	 *
	 * @param array $customers List of customer data to mock.
	 */
	protected function generate_customers_mock( array $customers ) {
		foreach ( $customers as $key => $data ) {
			$customer = $this->createMock( Customer::class );
			if ( isset( $data['id'] ) ) {
				$customer->method( 'getId' )->willReturn( $data['id'] );
			}
			if ( isset( $data['name'] ) ) {
				$customer->method( 'getDescriptiveName' )->willReturn( $data['name'] );
			}
			if ( isset( $data['manager'] ) ) {
				$customer->method( 'getManager' )->willReturn( $data['manager'] );
			}
			if ( isset( $data['test_account'] ) ) {
				$customer->method( 'getTestAccount' )->willReturn( $data['test_account'] );
			}
			if ( isset( $data['currency'] ) ) {
				$customer->method( 'getCurrencyCode' )->willReturn( $data['currency'] );
			}
			$customers[ $key ] = [ ( new GoogleAdsRow() )->setCustomer( $customer ) ];
		}

		$this->generate_ads_multiple_query_mock( $customers );
	}

	/**
	 * Generates a list of mocked customers resource names.
	 *
	 * @param array $list
	 */
	protected function generate_customer_list_mock( array $list ) {
		$customers = $this->createMock( ListAccessibleCustomersResponse::class );
		$customers->method( 'getResourceNames' )->willReturn( $list );

		$this->customer_service->method( 'listAccessibleCustomers' )->willReturn( $customers );
	}

	/**
	 * Generates a mocked exception when a list of customers is requested.
	 *
	 * @param ApiException $exception
	 */
	protected function generate_customer_list_mock_exception( ApiException $exception ) {
		$this->customer_service->method( 'listAccessibleCustomers' )->willThrowException( $exception );
	}

	/**
	 * Generates a mocked Merchant Center link.
	 *
	 * @param array $links
	 */
	protected function generate_mc_link_mock( array $links ) {
		$mc_link_service = $this->createMock( MerchantCenterLinkServiceClient::class );
		$this->client->method( 'getMerchantCenterLinkServiceClient' )->willReturn( $mc_link_service );

		$links = array_map(
			function( $link ) {
				return new MerchantCenterLink( $link );
			},
			$links
		);

		$list = $this->createMock( ListMerchantCenterLinksResponse::class );
		$list->method( 'getMerchantCenterLinks' )->willReturn( $links );

		$mc_link_service->method( 'listMerchantCenterLinks' )->willReturn( $list );

		return $mc_link_service;
	}

	/**
	 * Generates a mocked exception when a Merchant Center link is requested.
	 *
	 * @param ApiException $exception
	 */
	protected function generate_mc_link_mock_exception( ApiException $exception ) {
		$mc_link_service = $this->createMock( MerchantCenterLinkServiceClient::class );
		$this->client->method( 'getMerchantCenterLinkServiceClient' )->willReturn( $mc_link_service );

		$mc_link_service->method( 'listMerchantCenterLinks' )->willThrowException( $exception );
	}

	/**
	 * Generate a mocked mutate conversion action response.
	 * Asserts that set of operations contains an operation with the expected type.
	 *
	 * @param string $type      Mutation type we are expecting (create/update/remove).
	 * @param int    $action_id Conversion Action ID.
	 */
	protected function generate_conversion_action_mutate_mock( string $type, int $action_id ) {
		$result = $this->createMock( MutateConversionActionResult::class );
		$result->method( 'getResourceName' )->willReturn(
			ResourceNames::forConversionAction( $this->ads_id, $action_id )
		);

		$response = ( new MutateConversionActionsResponse() )->setResults( [ $result ] );

		$this->conversion_action_service->expects( $this->once() )
			->method( 'mutateConversionActions' )
			->willReturnCallback(
				function( int $ads_id, array $operations ) use ( $type, $response ) {
					// Assert that the operation is the right type.
					foreach ( $operations as $operation ) {
						if ( 'conversion_action_operation' === $operation->getOperation() ) {
							$operation = $operation->getConversionActionOperation();
							$this->assertEquals( $type, $operation->getOperation() );
						}
					}

					return $response;
				}
			);
	}

	/**
	 * Creates a mocked AdsConversionActionQuery response.
	 *
	 * @param array $data Conversion Action data.
	 */
	protected function generate_conversion_action_query_mock( array $data ) {
		$tag = $this->createMock( TagSnippet::class );
		$tag->method( 'getType' )->willReturn( TrackingCodeType::WEBPAGE );
		$tag->method( 'getPageFormat' )->willReturn( TrackingCodePageFormat::HTML );
		$tag->method( 'getEventSnippet' )->willReturn( $data['snippet'] );

		$conversion_action = $this->createMock( ConversionAction::class );
		$conversion_action->method( 'getId' )->willReturn( $data['id'] );
		$conversion_action->method( 'getName' )->willReturn( $data['name'] );
		$conversion_action->method( 'getStatus' )->willReturn( $data['status'] );
		$conversion_action->method( 'getTagSnippets' )->willReturn( [ $tag ] );

		$this->generate_ads_query_mock(
			[
				( new GoogleAdsRow() )->setConversionAction( $conversion_action ),
			]
		);
	}

	/**
	 * Generates a mocked exception when a ConversionAction is mutated.
	 *
	 * @param Exception $exception
	 */
	protected function generate_conversion_action_mutate_exception( Exception $exception ) {
		$this->conversion_action_service->method( 'mutateConversionActions' )->willThrowException( $exception );
	}

	/**
	 * Generates mocked report data.
	 *
	 * @param array  $data      Report rows.
	 * @param array  $args      Report arguments.
	 * @param string $next_page Token for the next page.
	 */
	protected function generate_ads_report_query_mock( array $data, array $args = [], string $next_page = '' ) {
		$page = $this->createMock( Page::class );
		$page->method( 'hasNextPage' )->willReturn( ! empty( $next_page ) );
		$page->method( 'getNextPageToken' )->willReturn( $next_page );
		$page->method( 'getIterator' )->willReturn(
			array_map(
				function( $row ) use ( $args ) {
					return $this->generate_report_row_mock( $row, $args );
				},
				$data
			)
		);

		$report = $this->createMock( PagedListResponse::class );
		$report->method( 'getPage' )->willReturn( $page );

		$this->service_client->method( 'search' )->willReturn( $report );
	}

	/**
	 * Converts report data to a mocked GoogleAdsRow.
	 *
	 * @param array $row  Report row to convert.
	 * @param array $args Report arguments.
	 *
	 * @return GoogleAdsRow
	 */
	protected function generate_report_row_mock( array $row, array $args ): GoogleAdsRow {
		$ads_row = new GoogleAdsRow();

		if ( ! empty( $row['campaign'] ) ) {
			$campaign = $this->createMock( Campaign::class );
			$campaign->method( 'getId' )->willReturn( $row['campaign']['id'] );
			$campaign->method( 'getName' )->willReturn( $row['campaign']['name'] );
			$campaign->method( 'getStatus' )->willReturn( AdsCampaignStatus::value( $row['campaign']['status'] ) );
			$campaign->method( 'getAdvertisingChannelType' )->willReturn( AdsCampaignType::value( $row['campaign']['type'] ) );
			$ads_row->setCampaign( $campaign );

			// Mock data for ShoppingPerformanceView
			$shopping_performance_view = $this->createMock( ShoppingPerformanceView::class );
			$ads_row->setShoppingPerformanceView( $shopping_performance_view );
		}

		if ( ! empty( $row['metrics'] ) && ! empty( $args['fields'] ) ) {
			$ads_row->setMetrics(
				$this->generate_report_metrics_mock( $row['metrics'], $args['fields'] )
			);
		}

		if ( ! empty( $row['segments'] ) ) {
			$segments = $this->createMock( Segments::class );

			if ( ! empty( $row['segments']['productItemId'] ) ) {
				$segments->method( 'getProductItemId' )->willReturn( $row['segments']['productItemId'] );
			}

			if ( ! empty( $row['segments']['productTitle'] ) ) {
				$segments->method( 'getProductTitle' )->willReturn( $row['segments']['productTitle'] );
			}

			if ( ! empty( $args['interval'] ) ) {
				switch ( $args['interval'] ) {
					case 'day':
						$segments->method( 'getDate' )->willReturn( $row['segments']['date'] );
						break;
					case 'week':
						$segments->method( 'getWeek' )->willReturn( $row['segments']['week'] );
						break;
					case 'month':
						$segments->method( 'getMonth' )->willReturn( $row['segments']['month'] );
						break;
					case 'quarter':
						$segments->method( 'getQuarter' )->willReturn( $row['segments']['quarter'] );
						break;
					case 'year':
						$segments->method( 'getYear' )->willReturn( $row['segments']['year'] );
						break;
				}
			}

			$ads_row->setSegments( $segments );
		}

		return $ads_row;
	}

	/**
	 * Return a mocked segment to include in the report row.
	 *
	 * @param array $data
	 * @param array $fields
	 * @return Segments
	 */
	protected function generate_report_metrics_mock( array $data, array $fields ): Metrics {
		$metrics = $this->createMock( Metrics::class );

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'clicks':
					$metrics->method( 'getClicks' )->willReturn( $data['clicks'] );
					break;
				case 'impressions':
					$metrics->method( 'getImpressions' )->willReturn( $data['impressions'] );
					break;
				case 'spend':
					$metrics->method( 'getCostMicros' )->willReturn( $data['costMicros'] );
					break;
				case 'sales':
					$metrics->method( 'getConversionsValue' )->willReturn( $data['conversionsValue'] );
					break;
				case 'conversions':
					$metrics->method( 'getConversions' )->willReturn( $data['conversions'] );
					break;
			}
		}

		return $metrics;
	}

	/**
	 * Converts asset group data to a mocked GoogleAdsRow.
	 *
	 * @param array $data AssetGroupAsset data to convert.
	 *
	 * @return GoogleAdsRow
	 */
	protected function generate_asset_group_row_mock( array $data ): GoogleAdsRow {
		$asset_group = $this->createMock( AssetGroup::class );
		$asset_group->method( 'getId' )->willReturn( $data['id'] );
		$asset_group->method( 'getFinalUrls' )->willReturn( new \ArrayIterator( [ $data['final_url'] ] ) );
		$asset_group->method( 'getPath1' )->willReturn( $data['display_url_path'][0] ?? '' );
		$asset_group->method( 'getPath2' )->willReturn( $data['display_url_path'] [1] ?? '' );

		return ( new GoogleAdsRow() )->setAssetGroup( $asset_group );
	}

	/**
	 * Generates mocked AdsAssetGroupQuery response.
	 *
	 * @param array $asset_group_responses Set of campaign data to convert.
	 */
	protected function generate_ads_asset_groups_query_mock( array $asset_group_responses ) {
		$asset_group_row_mock = array_map( [ $this, 'generate_asset_group_row_mock' ], $asset_group_responses );
		$page                 = $this->createMock( Page::class );

		$page->method( 'getIterator' )->willReturn( $asset_group_row_mock );

		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->method( 'getPage' )->willReturn(
			$page
		);

		$this->service_client->method( 'search' )->willReturn( $list_response );
	}


	/**
	 * Converts asset group assets data to a mocked GoogleAdsRow.
	 *
	 * @param array $data AssetGroupAsset data to convert.
	 *
	 * @return GoogleAdsRow
	 */
	protected function generate_asset_group_asset_row_mock( array $data ): GoogleAdsRow {
		$asset_group_asset = $this->createMock( AssetGroupAsset::class );
		$asset_group_asset->method( 'getFieldType' )->willReturn( $data['field_type'] );

		$asset_group = $this->createMock( AssetGroup::class );
		$asset_group->method( 'getId' )->willReturn( $data['asset_group_id'] );
		$asset_group->method( 'getPath1' )->willReturn( $data['path1'] ?? '' );
		$asset_group->method( 'getPath2' )->willReturn( $data['path2'] ?? '' );

		return ( new GoogleAdsRow() )
			->setAssetGroupAsset( $asset_group_asset )
			->setAssetGroup( $asset_group );
	}

	/**
	 * Generates mocked AdsAssetGroupAssetQuery response.
	 *
	 * @param array $asset_group_asset_responses Set of campaign data to convert.
	 */
	protected function generate_ads_asset_group_asset_query_mock( array $asset_group_asset_responses ) {
		$asset_group_asset_row_mock = array_map( [ $this, 'generate_asset_group_asset_row_mock' ], $asset_group_asset_responses );

		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->method( 'iterateAllElements' )->willReturn(
			$asset_group_asset_row_mock
		);

		$this->service_client->method( 'search' )->willReturn( $list_response );
	}

	/**
	 * Generate a mocked mutate asset group response.
	 * Asserts that set of operations contains an operation with the expected type.
	 *
	 * @param string $type            Mutation type we are expecting (create/update/remove).
	 * @param int    $asset_group_id  Asset Group ID we expect to see in the mutate result.
	 */
	protected function generate_asset_group_mutate_mock( string $type, int $asset_group_id ) {
		$asset_group_result = $this->createMock( MutateAssetGroupResult::class );
		$asset_group_result->method( 'getResourceName' )->willReturn(
			ResourceNames::forAssetGroup( $this->ads_id, $asset_group_id )
		);

		$response = ( new MutateGoogleAdsResponse() )->setMutateOperationResponses(
			[
				( new MutateOperationResponse() )->setAssetGroupResult( $asset_group_result ),
			]
		);

		$this->service_client->expects( $this->once() )
			->method( 'mutate' )
			->willReturnCallback(
				function( int $ads_id, array $operations ) use ( $type, $response ) {
					$operations_names = [];
					// Assert that the asset group operation is the right type.
					foreach ( $operations as $operation ) {
							$operation_name = $operation->getOperation();
						if ( 'asset_group_operation' === $operation_name ) {
							$asset_group_operation = $operation->getAssetGroupOperation();
							$this->assertEquals( $type, $asset_group_operation->getOperation() );
						}
						if ( 'asset_group_listing_group_filter_operation' === $operation_name ) {
							$asset_group_listing_group_filter_operation = $operation->getAssetGroupListingGroupFilterOperation();
							$this->assertEquals( $type, $asset_group_listing_group_filter_operation->getOperation() );
						}
						$operations_names[] = $operation_name;
					}

					if ( $type === 'create' ) {
						$this->assertEquals( 2, count( $operations_names ) );
						$this->assertContains( 'asset_group_operation', $operations_names );
						$this->assertContains( 'asset_group_listing_group_filter_operation', $operations_names );
					}
					if ( $type === 'update' ) {
						$this->assertEquals( 1, count( $operations_names ) );
						$this->assertContains( 'asset_group_operation', $operations_names );
					}

					return $response;
				}
			);

	}

	/**
	 * Generates a mocked exception when an resource is mutated.
	 *
	 * @param ApiException $exception
	 */
	protected function generate_mutate_mock_exception( ApiException $exception ) {
		$this->service_client->method( 'mutate' )->willThrowException( $exception );
	}

	/**
	 * Generates a Asset resource.
	 *
	 * @param array $asset The asset data.
	 * @return Asset|null The generated asset.
	 */
	protected function generate_asset( $asset ) {
		$ads_asset = new Asset();

		switch ( $asset['field_type'] ) {
			case AssetFieldType::LOGO:
			case AssetFieldType::MARKETING_IMAGE:
			case AssetFieldType::SQUARE_MARKETING_IMAGE:
				$image_asset = new ImageAsset( [ 'data' => $asset['content'] ] );
				$image_asset->setFullSize(
					new ImageDimension(
						[
							'url' => $asset['content'],
						]
					)
				);
				$ads_asset->setImageAsset( $image_asset );
				$ads_asset->setType( AssetType::IMAGE );
				return $ads_asset;
			case AssetFieldType::HEADLINE:
			case AssetFieldType::LONG_HEADLINE:
			case AssetFieldType::DESCRIPTION:
			case AssetFieldType::BUSINESS_NAME:
				$ads_asset->setTextAsset( new TextAsset( [ 'text' => $asset['content'] ] ) );
				$ads_asset->setType( AssetType::TEXT );
				return $ads_asset;
			case AssetFieldType::CALL_TO_ACTION_SELECTION:
				$ads_asset->setCallToActionAsset( new CallToActionAsset( [ 'call_to_action' => CallToActionType::number( $asset['content'] ) ] ) );
				$ads_asset->setType( AssetType::CALL_TO_ACTION );
				return $ads_asset;
			default:
				return null;
		}

	}

	/**
	 * Generate asset operations
	 *
	 * @param array $assets list of assets
	 */
	private function generate_crate_asset_operations( $assets = [] ) {
		foreach ( $assets as $asset ) {

			$ads_asset = $this->generate_asset( $asset );

			if ( $ads_asset ) {
				$asset_operations[] = ( new MutateOperation() )->setAssetOperation( ( new AssetOperation() )->setCreate( $ads_asset ) );
			}
		}

		return $asset_operations;
	}

	/**
	 * Generates Assets GoogleAdsRow.
	 *
	 * @param array $data AssetGroupAsset data to convert.
	 *
	 * @return GoogleAdsRow
	 */
	public function generate_asset_row_mock( array $data ): GoogleAdsRow {
		$asset = $this->generate_asset( $data );
		$asset->setId( $data['id'] );
		return ( new GoogleAdsRow() )->setAsset( $asset );
	}

	/**
	 * Generate a mocked mutate asset response.
	 * Asserts that set of operations contains an operation with the expected type and content.
	 *
	 * @param string $type   Mutation type we are expecting (create/update/remove).
	 * @param array  $asset  The asset to see in the mutate result.
	 */
	protected function generate_asset_mutate_mock( string $type, array $asset ) {
		$asset_result = $this->createMock( MutateAssetResult::class );
		$asset_result->method( 'getResourceName' )->willReturn(
			$this->generate_asset_resource_name( $asset['id'] )
		);

		$response = ( new MutateGoogleAdsResponse() )->setMutateOperationResponses(
			[
				( new MutateOperationResponse() )->setAssetResult( $asset_result ),
			]
		);

		$this->service_client->expects( $this->once() )
			->method( 'mutate' )
			->willReturnCallback(
				function( int $ads_id, array $operations ) use ( $type, $response, $asset ) {
					// Assert that the asset group operation is the right type.
					$operations_names = [];
					foreach ( $operations as $operation ) {
						$operations_names[] = $operation->getOperation();
						if ( 'asset_operation' === $operation->getOperation() ) {
							$asset_operation = $operation->getAssetOperation();
							$this->assertEquals( $type, $asset_operation->getOperation() );
							$this->assertEquals( $asset['content'], $this->get_asset_content( $asset_operation->getCreate(), $asset['field_type'] ) );
						}
					}

					$this->assertContains( 'asset_operation', $operations_names );

					return $response;
				}
			);

	}

	protected function generate_asset_batch_mutate_mock( $matcher, $asset_batches_callback ) {
		$this->service_client->expects( $matcher )
		->method( 'mutate' )
		->willReturnCallback(
			function( int $ads_id, array $operations ) use ( $matcher, $asset_batches_callback ) {
				$responses = [];

				$asset_batches_callback( $matcher, $operations );

				foreach ( $operations as $operation ) {
					$asset_result = $this->createMock( MutateAssetResult::class );
					$asset_result->method( 'getResourceName' )->willReturn(
						$operation->getAssetOperation()->getCreate()->getResourceName()
					);
					$responses[] = ( new MutateOperationResponse() )->setAssetResult( $asset_result );
				}

				return ( new MutateGoogleAdsResponse() )->setMutateOperationResponses(
					$responses
				);

			}
		);

	}

	/**
	 * Returns the asset content for the given row.
	 *
	 * @param Asset  $asset Data row returned from a query request.
	 * @param string $field_type The field type of the asset.
	 *
	 * @return string The asset content.
	 */
	protected function get_asset_content( Asset $asset, string $field_type ): string {
		switch ( $field_type ) {
			case AssetFieldType::LOGO:
			case AssetFieldType::MARKETING_IMAGE:
			case AssetFieldType::SQUARE_MARKETING_IMAGE:
				return $asset->getName();
			case AssetFieldType::HEADLINE:
			case AssetFieldType::LONG_HEADLINE:
			case AssetFieldType::DESCRIPTION:
			case AssetFieldType::BUSINESS_NAME:
				return $asset->getTextAsset()->getText();
			case AssetFieldType::CALL_TO_ACTION_SELECTION:
				// When CallToActionType::UNSPECIFIED is returned, does not have a CallToActionAsset.
				if ( ! $asset->getCallToActionAsset() ) {
					return CallToActionType::UNSPECIFIED;
				}
				return CallToActionType::label( $asset->getCallToActionAsset()->getCallToAction() );
			default:
				return '';
		}
	}



}
