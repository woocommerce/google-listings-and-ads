<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\V9\ResourceNames;
use Google\Ads\GoogleAds\V9\Common\LocationInfo;
use Google\Ads\GoogleAds\V9\Resources\AdGroup;
use Google\Ads\GoogleAds\V9\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V9\Resources\AdGroupCriterion;
use Google\Ads\GoogleAds\V9\Resources\Campaign;
use Google\Ads\GoogleAds\V9\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V9\Resources\CampaignCriterion;
use Google\Ads\GoogleAds\V9\Resources\Campaign\ShoppingSetting;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V9\Services\MutateGoogleAdsResponse;
use Google\Ads\GoogleAds\V9\Services\MutateCampaignResult;
use Google\Ads\GoogleAds\V9\Services\MutateOperationResponse;
use Google\ApiCore\ApiException;
use Google\ApiCore\PagedListResponse;

/**
 * Trait GoogleAdsClient
 *
 * @property int                               $ads_id
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
		$this->service_client->method( 'search' )->willReturn( $list_response );
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
	 * Generates a mocked AdsCampaignCriterionQuery response.
	 *
	 * @param string $campaign_criterion_resource_name
	 */
	protected function generate_campaign_criterion_query_mock( string $campaign_criterion_resource_name ) {
		$campaign_criterion = $this->createMock( CampaignCriterion::class );
		$campaign_criterion->method( 'getResourceName' )->willReturn( $campaign_criterion_resource_name );

		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->expects( $this->once() )
			->method( 'iterateAllElements' )
			->willReturn(
				[
					( new GoogleAdsRow )->setCampaignCriterion( $campaign_criterion ),
				]
			);

		$this->service_client->method( 'search' )->willReturn( $list_response );
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

		return ( new GoogleAdsRow )
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
	 * Generates a campaign criterion resource name.
	 *
	 * @param int $campaign_id
	 * @param int $campaign_critreion_id
	 */
	protected function generate_campaign_criterion_resource_name( int $campaign_id, int $campaign_criterion_id ) {
		return ResourceNames::forCampaignCriterion( $this->ads_id, $campaign_id, $campaign_criterion_id );
	}
}
