<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Settings as GoogleSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateShippingSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateShippingSettingsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateShippingSettingsTest extends UnitTest {

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|GoogleSettings $google_settings */
	protected $google_settings;

	/** @var MockObject|MarketService $market_service */
	protected $market_service;

	/** @var UpdateShippingSettings $job */
	protected $job;

	public function test_job_is_scheduled_when_market_service_has_syncable_markets() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( $this->job->get_process_item_hook() );

		$this->job->schedule();
	}

	public function test_job_is_not_scheduled_when_market_service_has_no_syncable_markets() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( false );

		$this->action_scheduler->expects( $this->never() )
			->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_job_is_not_scheduled_if_mc_not_connected() {
		$this->merchant_center->method( 'is_connected' )->willReturn( false );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->action_scheduler->expects( $this->never() )
			->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_process_items() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->google_settings->expects( $this->once() )->method( 'sync_shipping' );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function test_process_items_fails_if_mc_not_connected() {
		$this->merchant_center->method( 'is_connected' )->willReturn( false );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->google_settings->expects( $this->never() )->method( 'sync_shipping' );

		$this->expectException( JobException::class );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function test_process_items_fails_when_market_service_has_no_syncable_markets() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( false );

		$this->google_settings->expects( $this->never() )->method( 'sync_shipping' );

		$this->expectException( JobException::class );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionScheduler::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->merchant_center  = $this->createMock( MerchantCenterService::class );
		$this->google_settings  = $this->createMock( GoogleSettings::class );
		$this->market_service   = $this->createMock( MarketService::class );

		$this->job = new UpdateShippingSettings(
			$this->action_scheduler,
			$this->monitor,
			$this->merchant_center,
			$this->google_settings,
			$this->market_service
		);

		$this->job->init();
	}
}
