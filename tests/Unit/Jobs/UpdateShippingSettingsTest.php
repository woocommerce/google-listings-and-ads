<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings as GoogleSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateShippingSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateShippingSettingsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 *
 * @property MockObject|ActionScheduler           $action_scheduler
 * @property MockObject|ActionSchedulerJobMonitor $monitor
 * @property MockObject|MerchantCenterService     $merchant_center
 * @property MockObject|GoogleSettings            $google_settings
 * @property UpdateShippingSettings               $job
 */
class UpdateShippingSettingsTest extends UnitTest {

	public function test_job_is_scheduled() {
		$this->merchant_center->expects( $this->any() )
							  ->method( 'is_connected' )
							  ->willReturn( true );
		$this->google_settings->expects( $this->any() )
							  ->method( 'should_get_shipping_rates_from_woocommerce' )
							  ->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
							   ->method( 'schedule_immediate' )
							   ->with( $this->job->get_process_item_hook() );

		$this->job->schedule();
	}

	public function test_job_is_not_scheduled_if_shipping_not_set_to_automatic_sync() {
		$this->merchant_center->expects( $this->any() )
							  ->method( 'is_connected' )
							  ->willReturn( true );
		$this->google_settings->expects( $this->any() )
							  ->method( 'should_get_shipping_rates_from_woocommerce' )
							  ->willReturn( false );

		$this->action_scheduler->expects( $this->never() )
							   ->method( 'schedule_immediate' )
							   ->with( $this->job->get_process_item_hook() );

		$this->job->schedule();
	}

	public function test_job_is_not_scheduled_if_mc_not_connected() {
		$this->merchant_center->expects( $this->any() )
							  ->method( 'is_connected' )
							  ->willReturn( false );
		$this->google_settings->expects( $this->any() )
							  ->method( 'should_get_shipping_rates_from_woocommerce' )
							  ->willReturn( true );

		$this->action_scheduler->expects( $this->never() )
							   ->method( 'schedule_immediate' )
							   ->with( $this->job->get_process_item_hook() );

		$this->job->schedule();
	}

	public function test_process_items() {
		$this->merchant_center->expects( $this->any() )
							  ->method( 'is_connected' )
							  ->willReturn( true );
		$this->google_settings->expects( $this->any() )
							  ->method( 'should_get_shipping_rates_from_woocommerce' )
							  ->willReturn( true );

		$this->google_settings->expects( $this->once() )
							  ->method( 'sync_shipping' );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function test_process_items_fails_if_mc_not_connected() {
		$this->merchant_center->expects( $this->any() )
							  ->method( 'is_connected' )
							  ->willReturn( false );
		$this->google_settings->expects( $this->any() )
							  ->method( 'should_get_shipping_rates_from_woocommerce' )
							  ->willReturn( true );

		$this->google_settings->expects( $this->never() )
							  ->method( 'sync_shipping' );

		$this->expectException( JobException::class );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function test_process_items_fails_if_shipping_not_set_to_automatic_sync() {
		$this->merchant_center->expects( $this->any() )
							  ->method( 'is_connected' )
							  ->willReturn( true );
		$this->google_settings->expects( $this->any() )
							  ->method( 'should_get_shipping_rates_from_woocommerce' )
							  ->willReturn( false );

		$this->google_settings->expects( $this->never() )
							  ->method( 'sync_shipping' );

		$this->expectException( JobException::class );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionScheduler::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->merchant_center  = $this->createMock( MerchantCenterService::class );
		$this->google_settings  = $this->createMock( GoogleSettings::class );
		$this->job              = new UpdateShippingSettings( $this->action_scheduler, $this->monitor, $this->merchant_center, $this->google_settings );

		$this->job->init();
	}

}
