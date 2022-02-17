<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\V9\ResourceNames;
use Google\Ads\GoogleAds\V9\Resources\AssetGroup;
use Google\Ads\GoogleAds\V9\Resources\AssetGroupListingGroupFilter;
use Google\Ads\GoogleAds\V9\Resources\Campaign;
use Google\Ads\GoogleAds\V9\Resources\CampaignBudget;
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
	 * Generates a mocked AdsAssetGroupQuery response.
	 *
	 * @param string $asset_group_resource_name
	 * @param string $listing_group_resource_name
	 */
	protected function generate_ads_asset_group_query_mock( string $asset_group_resource_name, string $listing_group_resource_name ) {
		$asset_group = $this->createMock( AssetGroup::class );
		$asset_group->method( 'getResourceName' )->willReturn( $asset_group_resource_name );

		$listing_group = $this->createMock( AssetGroupListingGroupFilter::class );
		$listing_group->method( 'getResourceName' )->willReturn( $listing_group_resource_name );

		$list_response = $this->createMock( PagedListResponse::class );
		$list_response->expects( $this->exactly( 2 ) )
			->method( 'iterateAllElements' )
			->will(
				$this->onConsecutiveCalls(
					[
						( new GoogleAdsRow )->setAssetGroup( $asset_group ),
					],
					[
						( new GoogleAdsRow )->setAssetGroupListingGroupFilter( $listing_group ),
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
		$campaign->method( 'getAdvertisingChannelType' )->willReturn( CampaignType::number( $data['type'] ) );
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
	 * Generates an asset group resource name.
	 *
	 * @param int $asset_group_id
	 */
	protected function generate_asset_group_resource_name( int $asset_group_id ) {
		return ResourceNames::forAssetGroup( $this->ads_id, $asset_group_id );
	}

	/**
	 * Generates an asset group asset resource name.
	 *
	 * @param int $asset_group_id
	 * @param int $listing_group_id
	 */
	protected function generate_listing_group_resource_name( int $asset_group_id, int $listing_group_id ) {
		// No helper function available in the Google Ads library.
		return "customers/{$this->ads_id}/assetGroupListingGroupFilters/{$asset_group_id}~{$listing_group_id}";
	}
}
