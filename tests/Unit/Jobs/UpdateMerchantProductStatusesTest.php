<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\Product;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
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

	/** @var MockObject|MapiProductsService $mapi_products */
	protected $mapi_products;

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
		$this->mapi_products           = $this->createMock( MapiProductsService::class );
		$this->merchant_statuses       = $this->createMock( MerchantStatuses::class );
		$this->job                     = new UpdateMerchantProductStatuses(
			$this->action_scheduler,
			$this->monitor,
			$this->merchant_center_service,
			$this->mapi_products,
			$this->merchant_statuses
		);

		$this->job->init();
	}

	/**
	 * Build a minimal Merchant API product for the given id.
	 *
	 * @param string $google_id
	 *
	 * @return Product
	 */
	protected function make_product( string $google_id ): Product {
		return Product::from_array(
			[
				'name'    => 'accounts/12345/products/' . $google_id,
				'offerId' => explode( '~', $google_id )[2],
			]
		);
	}

	public function test_update_merchant_product_statuses_not_connected() {
		$this->merchant_center_service->method( 'is_connected' )
			->willReturn( false );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_update_merchant_product_statuses() {
		$this->merchant_center_service->method( 'is_connected' )
			->willReturn( true );

		$pages = [
			[
				'products'        => [ $this->make_product( 'en~US~gla_1' ) ],
				'next_page_token' => 'ABC=',
			],
			[
				'products'        => [ $this->make_product( 'en~US~gla_2' ) ],
				'next_page_token' => 'DEF=',
			],
			[
				'products'        => [ $this->make_product( 'en~US~gla_3' ) ],
				'next_page_token' => null,
			],
		];

		$matcher = $this->exactly( 3 );
		$this->mapi_products->expects( $matcher )
			->method( 'list_page' )
			->will(
				$this->returnCallback(
					function ( $next_page_token ) use ( $matcher, $pages ) {
						$invocation_count = $matcher->getInvocationCount();

						if ( $invocation_count === 1 ) {
							$this->assertNull( $next_page_token );
						}
						if ( $invocation_count === 2 ) {
							$this->assertEquals( 'ABC=', $next_page_token );
						}
						if ( $invocation_count === 3 ) {
							$this->assertEquals( 'DEF=', $next_page_token );
						}

						if ( ! isset( $pages[ $invocation_count - 1 ] ) ) {
							throw new Exception( 'Invalid next page token' );
						}

						return $pages[ $invocation_count - 1 ];
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
				->method( 'process_mapi_products' )
				->willReturnCallback(
					function ( $products ) use ( $matcher, $pages ) {
						$invocation_count = $matcher->getInvocationCount();

						$this->assertEquals( $pages[ $invocation_count - 1 ]['products'], $products );
					}
				);

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'handle_complete_mc_statuses_fetching' );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'clear_product_statuses_cache_and_issues' );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'refresh_account_and_presync_issues' );

		$this->job->schedule();
	}

	public function test_page_size_filter_is_applied() {
		$this->merchant_center_service->method( 'is_connected' )->willReturn( true );

		add_filter( 'woocommerce_gla_product_view_report_page_size', fn() => 250 );

		$this->mapi_products->expects( $this->once() )
			->method( 'list_page' )
			->with( null, 250 )
			->willReturn(
				[
					'products'        => [],
					'next_page_token' => null,
				]
			);

		do_action( self::PROCESS_ITEM_HOOK, [] );

		remove_all_filters( 'woocommerce_gla_product_view_report_page_size' );
	}

	public function test_page_size_filter_is_clamped_to_the_api_maximum() {
		$this->merchant_center_service->method( 'is_connected' )->willReturn( true );

		add_filter( 'woocommerce_gla_product_view_report_page_size', fn() => 5000 );

		$this->mapi_products->expects( $this->once() )
			->method( 'list_page' )
			->with( null, 1000 )
			->willReturn(
				[
					'products'        => [],
					'next_page_token' => null,
				]
			);

		do_action( self::PROCESS_ITEM_HOOK, [] );

		remove_all_filters( 'woocommerce_gla_product_view_report_page_size' );
	}

	public function test_a_zero_string_page_token_does_not_restart_the_refresh() {
		$this->merchant_center_service->method( 'is_connected' )->willReturn( true );

		$this->merchant_statuses->expects( $this->never() )
			->method( 'clear_product_statuses_cache_and_issues' );
		$this->merchant_statuses->expects( $this->never() )
			->method( 'refresh_account_and_presync_issues' );

		$this->mapi_products->expects( $this->once() )
			->method( 'list_page' )
			->with( '0', 1000 )
			->willReturn(
				[
					'products'        => [],
					'next_page_token' => null,
				]
			);

		do_action( self::PROCESS_ITEM_HOOK, [ 'next_page_token' => '0' ] );
	}

	public function test_a_zero_string_page_token_continues_pagination() {
		$this->merchant_center_service->method( 'is_connected' )->willReturn( true );

		$this->mapi_products->expects( $this->once() )
			->method( 'list_page' )
			->willReturn(
				[
					'products'        => [],
					'next_page_token' => '0',
				]
			);

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK, [ [ 'next_page_token' => '0' ] ] );

		$this->merchant_statuses->expects( $this->never() )
			->method( 'handle_complete_mc_statuses_fetching' );

		do_action( self::PROCESS_ITEM_HOOK, [] );
	}

	public function test_update_merchant_product_statuses_when_view_report_throws_error() {
		$this->merchant_center_service->method( 'is_connected' )
		->willReturn( true );

		$this->mapi_products->expects( $this->exactly( 1 ) )
		->method( 'list_page' )
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

		$this->mapi_products->expects( $this->exactly( 1 ) )
		->method( 'list_page' )
		->willThrowException( new Exception( 'error' ) );

		$this->merchant_statuses->expects( $this->exactly( 1 ) )
			->method( 'handle_failed_mc_statuses_fetching' )
			->with( 'error' );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Error updating merchant product statuses: error' );

		do_action( self::PROCESS_ITEM_HOOK, [] );
	}

	public function test_get_failure_rate_message() {
		$this->merchant_center_service->method( 'is_connected' )
		->willReturn( true );

		$this->monitor->expects( $this->exactly( 1 ) )->method( 'validate_failure_rate' )
		->willThrowException( new JobException( 'The "update_merchant_product_statuses" job was stopped because its failure rate is above the allowed threshold.' ) );

		$this->assertEquals( 'The "update_merchant_product_statuses" job was stopped because its failure rate is above the allowed threshold.', $this->job->get_failure_rate_message() );
	}

	public function test_get_with_no_failure_rate_message() {
		$this->merchant_center_service->method( 'is_connected' )
		->willReturn( true );

		$this->monitor->expects( $this->exactly( 1 ) )->method( 'validate_failure_rate' );

		$this->assertNull( $this->job->get_failure_rate_message() );
	}

	public function test_is_running() {
		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( self::PROCESS_ITEM_HOOK, null )
			->willReturn( true );

		$this->assertTrue( $this->job->is_scheduled() );
	}
}
