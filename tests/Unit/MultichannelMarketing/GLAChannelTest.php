<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MultichannelMarketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MultichannelMarketing\GLAChannel;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class GLAChannelTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MultichannelMarketing
 *
 * @property GLAChannel                       $gla_channel
 * @property MockObject|MerchantCenterService $merchant_center
 * @property MockObject|AdsCampaign           $ads_campaign
 * @property MockObject|Ads                   $ads
 * @property MockObject|MerchantStatuses      $merchant_statuses
 * @property MockObject|ProductSyncStats      $product_sync_stats
 * @property MockObject|WC                    $wc
 */
class GLAChannelTest extends UnitTest {

	public function test_get_slug_is_not_empty() {
		$this->assertNotEmpty( $this->gla_channel->get_slug() );
	}

	public function test_get_name_is_not_empty() {
		$this->assertNotEmpty( $this->gla_channel->get_name() );
	}

	public function test_get_description_is_not_empty() {
		$this->assertNotEmpty( $this->gla_channel->get_description() );
	}

	public function test_get_icon_url_is_valid() {
		$this->assertNotEmpty( $this->gla_channel->get_icon_url() );
		$this->assertNotFalse( filter_var( $this->gla_channel->get_icon_url(), FILTER_VALIDATE_URL ) );
	}

	public function test_get_is_setup_completed_returns_true() {
		$this->merchant_center->expects( $this->once() )->method( 'is_setup_complete' )->willReturn( true );
		$this->assertTrue( $this->gla_channel->is_setup_completed() );
	}

	public function test_get_is_setup_completed_returns_false() {
		$this->merchant_center->expects( $this->once() )->method( 'is_setup_complete' )->willReturn( false );
		$this->assertFalse( $this->gla_channel->is_setup_completed() );
	}

	public function test_get_setup_url_is_valid() {
		$this->assertNotEmpty( $this->gla_channel->get_setup_url() );
		$this->assertNotFalse( filter_var( $this->gla_channel->get_setup_url(), FILTER_VALIDATE_URL ) );
	}

	public function test_get_setup_url_changes_based_on_setup_status() {
		// Return TRUE the first time `is_setup_complete` is called.
		$this->merchant_center->expects( $this->at( 0 ) )->method( 'is_setup_complete' )->willReturn( true );

		// Return FALSE the second time `is_setup_complete` is called. To test that the setup URL changes.
		$this->merchant_center->expects( $this->at( 1 ) )->method( 'is_setup_complete' )->willReturn( false );

		$setup_url_complete = $this->gla_channel->get_setup_url();

		$this->assertNotEquals( $setup_url_complete, $this->gla_channel->get_setup_url() );
	}

	public function test_get_product_listings_status_returns_not_applicable_if_not_sync_ready() {
		$this->merchant_center->expects( $this->once() )->method( 'is_ready_for_syncing' )->willReturn( false );
		$this->assertEquals( MarketingChannelInterface::PRODUCT_LISTINGS_NOT_APPLICABLE, $this->gla_channel->get_product_listings_status() );
	}

	public function test_get_product_listings_status_returns_in_progress_if_there_are_product_sync_jobs_in_queue() {
		$this->merchant_center->expects( $this->once() )->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->product_sync_stats->expects( $this->once() )->method( 'get_count' )->willReturn( 1 );
		$this->assertEquals( MarketingChannelInterface::PRODUCT_LISTINGS_SYNC_IN_PROGRESS, $this->gla_channel->get_product_listings_status() );
	}

	public function test_get_product_listings_status_returns_synced_if_there_are_no_product_sync_jobs_in_queue() {
		$this->merchant_center->expects( $this->once() )->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->product_sync_stats->expects( $this->once() )->method( 'get_count' )->willReturn( 0 );
		$this->assertEquals( MarketingChannelInterface::PRODUCT_LISTINGS_SYNCED, $this->gla_channel->get_product_listings_status() );
	}

	public function test_get_errors_count_returns_total_number_of_issues() {
		$this->merchant_statuses
			->expects( $this->once() )
			->method( 'get_issues' )
			->willReturn(
				[
					'issues' => [
						[
							'type'  => 'account',
							'issue' => 'test issue #1',
						],
						[
							'type'  => 'product',
							'issue' => 'test issue #2',
						],
					],
					'total'  => 2,
				]
			);
		$this->assertEquals( 2, $this->gla_channel->get_errors_count() );
	}

	public function test_get_errors_count_returns_zero_if_there_is_an_exception_getting_the_issues() {
		$this->merchant_statuses
			->expects( $this->once() )
			->method( 'get_issues' )
			->willThrowException( new Exception( 'Error retrieving issues!' ) );

		$this->assertEquals( 0, $this->gla_channel->get_errors_count() );
	}

	public function test_get_supported_campaign_types_returns_ads_campaign() {
		$this->assertCount( 1, $this->gla_channel->get_supported_campaign_types() );
		$this->assertContainsOnlyInstancesOf( MarketingCampaignType::class, $this->gla_channel->get_supported_campaign_types() );
		$this->assertArrayHasKey( 'google-ads', $this->gla_channel->get_supported_campaign_types() );
		$this->assertEquals( 'google-ads', $this->gla_channel->get_supported_campaign_types()['google-ads']->get_id() );
	}

	public function test_get_campaigns_returns_empty_if_no_ads_id_exists() {
		$this->ads->expects( $this->once() )->method( 'ads_id_exists' )->willReturn( false );

		$this->assertEmpty( $this->gla_channel->get_campaigns() );
	}

	public function test_get_campaigns_returns_empty_if_there_is_an_exception_getting_the_campaigns() {
		$this->ads->expects( $this->once() )->method( 'ads_id_exists' )->willReturn( true );
		$this->ads_campaign
			->expects( $this->once() )
			->method( 'get_campaigns' )
			->willThrowException( new ExceptionWithResponseData( 'Error retrieving issues!' ) );

		$this->assertEmpty( $this->gla_channel->get_campaigns() );
	}

	public function test_get_campaigns_returns_marketing_campaigns() {
		$this->wc->expects( $this->once() )->method( 'get_woocommerce_currency' )->willReturn( 'USD' );

		$this->ads->expects( $this->once() )->method( 'ads_id_exists' )->willReturn( true );
		$this->ads_campaign
			->expects( $this->once() )
			->method( 'get_campaigns' )
			->willReturn(
				[
					[
						'id'     => '1111',
						'name'   => 'Test Campaign #1111',
						'amount' => 1000,
					],
				]
			);

		$campaigns = $this->gla_channel->get_campaigns();
		$this->assertCount( 1, $campaigns );
		$this->assertContainsOnlyInstancesOf( MarketingCampaign::class, $campaigns );

		$campaign_1 = $campaigns[0];
		$this->assertEquals( '1111', $campaign_1->get_id() );
		$this->assertEquals( 'Test Campaign #1111', $campaign_1->get_title() );
		$this->assertNotNull( $campaign_1->get_cost() );
		$this->assertEquals( '1000', $campaign_1->get_cost()->get_value() );
		$this->assertEquals( 'USD', $campaign_1->get_cost()->get_currency() );
		$this->assertNotEmpty( $campaign_1->get_manage_url() );
		$this->assertNotFalse( filter_var( $campaign_1->get_manage_url(), FILTER_VALIDATE_URL ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->merchant_center    = $this->createMock( MerchantCenterService::class );
		$this->ads_campaign       = $this->createMock( AdsCampaign::class );
		$this->ads                = $this->createMock( Ads::class );
		$this->merchant_statuses  = $this->createMock( MerchantStatuses::class );
		$this->product_sync_stats = $this->createMock( ProductSyncStats::class );
		$this->wc                 = $this->createMock( WC::class );

		$this->gla_channel = new GLAChannel(
			$this->merchant_center,
			$this->ads_campaign,
			$this->ads,
			$this->merchant_statuses,
			$this->product_sync_stats,
			$this->wc
		);
	}

}
