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
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
use Exception;
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

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

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

	public function test_process_items_stores_failure_and_rethrows_when_sync_fails() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->google_settings->method( 'sync_shipping' )
			->willThrowException( new Exception( 'API rejected the settings.' ) );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::SHIPPING_SYNC_FAILURE,
				$this->callback(
					function ( $failure ) {
						return 'API rejected the settings.' === $failure['message'] && ! empty( $failure['failed_at'] );
					}
				)
			);
		$this->merchant_statuses->expects( $this->once() )->method( 'clear_cache' );

		$this->expectException( Exception::class );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function test_process_items_clears_stored_failure_after_successful_sync() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->options->method( 'get' )
			->with( OptionsInterface::SHIPPING_SYNC_FAILURE )
			->willReturn(
				[
					'message'   => 'API rejected the settings.',
					'failed_at' => '2026-07-09 10:00:00',
				]
			);

		$this->google_settings->expects( $this->once() )->method( 'sync_shipping' );
		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::SHIPPING_SYNC_FAILURE );
		$this->merchant_statuses->expects( $this->once() )->method( 'clear_cache' );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function test_process_items_success_without_stored_failure_clears_nothing() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$this->market_service->method( 'has_syncable_markets' )->willReturn( true );

		$this->options->method( 'get' )
			->with( OptionsInterface::SHIPPING_SYNC_FAILURE )
			->willReturn( null );

		$this->options->expects( $this->never() )->method( 'delete' );
		$this->merchant_statuses->expects( $this->never() )->method( 'clear_cache' );

		do_action( $this->job->get_process_item_hook(), [] );
	}

	public function test_stored_failure_is_added_as_merchant_issue() {
		$this->options->method( 'get' )
			->with( OptionsInterface::SHIPPING_SYNC_FAILURE )
			->willReturn(
				[
					'message'   => 'API rejected the settings.',
					'failed_at' => '2026-07-09 10:00:00',
				]
			);

		$cache_created_time = new DateTime( '2026-07-09 11:00:00' );
		$issues             = apply_filters( 'woocommerce_gla_custom_merchant_issues', [], $cache_created_time );

		$this->assertCount( 1, $issues );
		$this->assertSame( 'shipping_settings_sync_failed', $issues[0]['code'] );
		$this->assertSame( 'error', $issues[0]['severity'] );
		$this->assertSame( '2026-07-09 11:00:00', $issues[0]['created_at'] );
		$this->assertStringContainsString( 'API rejected the settings.', $issues[0]['action'] );
	}

	public function test_no_merchant_issue_without_stored_failure() {
		$this->options->method( 'get' )
			->with( OptionsInterface::SHIPPING_SYNC_FAILURE )
			->willReturn( null );

		$issues = apply_filters( 'woocommerce_gla_custom_merchant_issues', [], new DateTime() );

		$this->assertSame( [], $issues );
	}

	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler  = $this->createMock( ActionScheduler::class );
		$this->monitor           = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->merchant_center   = $this->createMock( MerchantCenterService::class );
		$this->google_settings   = $this->createMock( GoogleSettings::class );
		$this->market_service    = $this->createMock( MarketService::class );
		$this->merchant_statuses = $this->createMock( MerchantStatuses::class );
		$this->options           = $this->createMock( OptionsInterface::class );

		$this->job = new UpdateShippingSettings(
			$this->action_scheduler,
			$this->monitor,
			$this->merchant_center,
			$this->google_settings,
			$this->market_service,
			$this->merchant_statuses
		);
		$this->job->set_options_object( $this->options );

		$this->job->init();
	}
}
