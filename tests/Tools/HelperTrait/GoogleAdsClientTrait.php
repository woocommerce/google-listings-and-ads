<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\V9\ResourceNames;
use Google\Ads\GoogleAds\V9\Enums\AccessRoleEnum\AccessRole;
use Google\Ads\GoogleAds\V9\Resources\AdGroup;
use Google\Ads\GoogleAds\V9\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V9\Resources\AdGroupCriterion;
use Google\Ads\GoogleAds\V9\Resources\BillingSetup;
use Google\Ads\GoogleAds\V9\Resources\Campaign;
use Google\Ads\GoogleAds\V9\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V9\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V9\Resources\Customer;
use Google\Ads\GoogleAds\V9\Resources\CustomerUserAccess;
use Google\Ads\GoogleAds\V9\Resources\MerchantCenterLink;
use Google\Ads\GoogleAds\V9\Services\CustomerServiceClient;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V9\Services\ListAccessibleCustomersResponse;
use Google\Ads\GoogleAds\V9\Services\ListMerchantCenterLinksResponse;
use Google\Ads\GoogleAds\V9\Services\MerchantCenterLinkServiceClient;
use Google\Ads\GoogleAds\V9\Services\MutateGoogleAdsResponse;
use Google\Ads\GoogleAds\V9\Services\MutateCampaignResult;
use Google\Ads\GoogleAds\V9\Services\MutateOperationResponse;
use Google\ApiCore\ApiException;
use Google\ApiCore\PagedListResponse;

/**
 * Trait GoogleAdsClient
 *
 * @property int                               $ads_id
 * @property MockObject|CustomerServiceClient  $customer_service
 * @property MockObject|GoogleAdsClient        $client
 * @property MockObject|GoogleAdsServiceClient $service_client
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
					foreach( $operations as $operation ) {
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
	 * Generates a mocked AdsCampaignQuery response.
	 *
	 * @param array $responses Set of campaign data to convert.
	 */
	protected function generate_ads_campaign_query_mock( array $responses ) {
		$this->generate_ads_query_mock(
			array_map( [ $this, 'generate_campaign_row_mock' ], $responses )
		);
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
				( new GoogleAdsRow )->setCampaign( $campaign ),
			]
		);
	}

	/**
	 * Generates a mocked AdsGroupQuery response.
	 *
	 * @param string $ad_group_resource_name
	 * @param string $ad_group_ad_resource_name
	 * @param string $listing_group_resource_name
	 */
	protected function generate_ads_group_query_mock(
		string $ad_group_resource_name,
		string $ad_group_ad_resource_name,
		string $listing_group_resource_name
	) {
		$ad_group = $this->createMock( AdGroup::class );
		$ad_group->method( 'getResourceName' )->willReturn( $ad_group_resource_name );

		$ad_group_ad = $this->createMock( AdGroupAd::class );
		$ad_group_ad->method( 'getResourceName' )->willReturn( $ad_group_ad_resource_name );

		$listing_group = $this->createMock( AdGroupCriterion::class );
		$listing_group->method( 'getResourceName' )->willReturn( $listing_group_resource_name );

		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->expects( $this->exactly( 3 ) )
			->method( 'iterateAllElements' )
			->will(
				$this->onConsecutiveCalls(
					[
						( new GoogleAdsRow )->setAdGroup( $ad_group ),
					],
					[
						( new GoogleAdsRow )->setAdGroupAd( $ad_group_ad ),
					],
					[
						( new GoogleAdsRow )->setAdGroupCriterion( $listing_group ),
					]
				)
			);

		$this->service_client->method( 'search' )->willReturn( $list_response );
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
				( new GoogleAdsRow )->setBillingSetup( $billing_setup ),
			]
		);
	}

	/**
	 * Generates a mocked AdsAccountQuery response.
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
				( new GoogleAdsRow )->setCustomerUserAccess( $access ),
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
		$campaign->method( 'getShoppingSetting' )->willReturn( $setting );

		$budget = $this->createMock( CampaignBudget::class );
		$budget->method( 'getAmountMicros' )->willReturn( $this->to_micro( $data['amount'] ) );

		return ( new GoogleAdsRow )
			->setCampaign( $campaign )
			->setCampaignBudget( $budget );
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
	 * Generates an ad group ad resource name.
	 *
	 * @param int $ad_group_ad_id
	 */
	protected function generate_ad_group_ad_resource_name( int $ad_group_id, int $ad_group_ad_id ) {
		return ResourceNames::forAdGroupAd( $this->ads_id, $ad_group_id, $ad_group_ad_id );
	}

	/**
	 * Generates an ad group criterion resource name.
	 *
	 * @param int $ad_group_id
	 * @param int $listing_group_id
	 */
	protected function generate_listing_group_resource_name( int $ad_group_id, int $listing_group_id ) {
		return ResourceNames::forAdGroupCriterion( $this->ads_id, $ad_group_id, $listing_group_id );
	}

	/**
	 * Generates a mocked customer.
	 *
	 * @param string $currency
	 */
	protected function generate_customer_mock( string $currency ) {
		$customer = $this->createMock( Customer::class );
		$customer->method( 'getCurrencyCode' )->willReturn( $currency );

		$this->customer_service->method( 'getCustomer' )->willReturn( $customer );
	}

	/**
	 * Generates a mocked exception when a customer is requested.
	 *
	 * @param ApiException $exception
	 */
	protected function generate_customer_mock_exception( ApiException $exception ) {
		$this->customer_service->method( 'getCustomer' )->willThrowException( $exception );
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
}
