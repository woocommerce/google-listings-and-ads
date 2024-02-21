<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantReport;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;
use Error;

/**
 * Class UpdateMerchantProductStatusesTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateMerchantProductStatusesTest extends UnitTest {

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|MerchantCenterService $merchant_center_service */
	protected $merchant_center_service;

	/** @var MockObject|MerchantReport $merchant_report */
	protected $merchant_report;

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var UpdateSyncableProductsCount $job */
	protected $job;

	protected const JOB_NAME          = 'update_merchant_product_statuses';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler        = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor                 = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->merchant_center_service = $this->createMock( MerchantCenterService::class );
		$this->merchant_report         = $this->createMock( MerchantReport::class );
		$this->merchant_statuses       = $this->createMock( MerchantStatuses::class );
		$this->job                     = new UpdateMerchantProductStatuses(
			$this->action_scheduler,
			$this->monitor,
			$this->merchant_center_service,
			$this->merchant_report,
			$this->merchant_statuses
		);

		$this->job->init();
	}

	public function test_update_merchant_product_statuses_not_connected() {
		$this->merchant_center_service->method( 'is_connected' )
			->willReturn( false );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_update_merchant_product_statuses() {
		$this->merchant_center_service->method( 'is_connected' )
			->willReturn( true );

		$matcher = $this->exactly( 3 );
		$this->merchant_report->expects( $matcher )
			->method( 'get_product_view_report' )
			->will(
				$this->returnCallback(
					function ( $next_page_token ) use ( $matcher ) {
						$invocation_count = $matcher->getInvocationCount();

						if ( $invocation_count === 1 ) {

							$this->assertNull( $next_page_token );
							return [
								'statuses'        => [
									[
										'product_id' => 1,
										'status'     => MCStatus::APPROVED,
									],
								],
								'next_page_token' => 'ABC=',
							];
						}

						if ( $invocation_count === 2 ) {
							$this->assertEquals( 'ABC=', $next_page_token );
							return [
								'statuses'        => [
									[
										'product_id' => 2,
										'status'     => MCStatus::APPROVED,
									],
								],
								'next_page_token' => 'DEF=',
							];
						}

						if ( $invocation_count === 3 ) {
							$this->assertEquals( 'DEF=', $next_page_token );
							return [
								'statuses'        => [
									[
										'product_id' => 3,
										'status'     => MCStatus::APPROVED,
									],
								],
								'next_page_token' => null,
							];
						}

						throw new Exception( 'Invalid next page token' );
					}
				)
			);

		$matcher = $this->exactly( 3 );
		$this->action_scheduler->expects( $matcher )
			->method( 'schedule_immediate' )
			->willReturnCallback(
				function ( $hook_name, $args ) use ( $matcher ) {
					$invocation_count = $matcher->getInvocationCount();

					if ( $invocation_count === 1 ) {
						$this->assertEquals( [], $args );
					}

					if ( $invocation_count === 2 ) {
						$this->assertEquals( [ 'next_page_token' => 'ABC=' ], $args[0] );
					}

					if ( $invocation_count === 3 ) {
						$this->assertEquals( [ 'next_page_token' => 'DEF=' ], $args[0] );
					}

					do_action( self::PROCESS_ITEM_HOOK, $args[0] ?? [] );

					return $matcher->getInvocationCount();
				}
			);

			$matcher = $this->exactly( 3 );
			$this->merchant_statuses->expects( $matcher )
				->method( 'update_product_stats' )
				->willReturnCallback(
					function ( $statuses ) use ( $matcher ) {
						$invocation_count = $matcher->getInvocationCount();

						if ( $invocation_count === 1 ) {
							$this->assertEquals(
								[
									[
										'product_id' => 1,
										'status'     => MCStatus::APPROVED,
									],
								],
								$statuses
							);
						}

						if ( $invocation_count === 2 ) {
							$this->assertEquals(
								[
									[
										'product_id' => 2,
										'status'     => MCStatus::APPROVED,
									],
								],
								$statuses
							);
						}

						if ( $invocation_count === 3 ) {
							$this->assertEquals(
								[
									[
										'product_id' => 3,
										'status'     => MCStatus::APPROVED,
									],
								],
								$statuses
							);
						}
					}
				);

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'handle_complete_mc_statuses_fetching' );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'clear_cache' );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'delete_product_statuses_count_intermediate_data' );

		$this->job->schedule();
	}

	public function test_update_merchant_product_statuses_when_view_report_throws_error() {
		$this->merchant_center_service->method( 'is_connected' )
		->willReturn( true );

		$this->merchant_report->expects( $this->exactly( 1 ) )
		->method( 'get_product_view_report' )
		->willThrowException( new Error( 'error' ) );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'handle_failed_mc_statuses_fetching' )
			->with( 'error' );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Error updating merchant product statuses: error' );

		do_action( self::PROCESS_ITEM_HOOK, [] );
	}

	public function test_update_merchant_product_statuses_when_view_report_throws_exception() {
		$this->merchant_center_service->method( 'is_connected' )
		->willReturn( true );

		$this->merchant_report->expects( $this->exactly( 1 ) )
		->method( 'get_product_view_report' )
		->willThrowException( new Exception( 'error' ) );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'handle_failed_mc_statuses_fetching' )
			->with( 'error' );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Error updating merchant product statuses: error' );

		do_action( self::PROCESS_ITEM_HOOK, [] );
	}
}
