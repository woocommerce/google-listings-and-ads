<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupOrphanedMarketProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class CleanupOrphanedMarketProductsJobTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class CleanupOrphanedMarketProductsJobTest extends UnitTest {

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|ProductSyncer $product_syncer */
	protected $product_syncer;

	/** @var MockObject|ProductRepository $product_repository */
	protected $product_repository;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var CleanupOrphanedMarketProductsJob $job */
	protected $job;

	protected const JOB_NAME          = 'cleanup_orphaned_market_products_job';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionScheduler::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer     = $this->createMock( ProductSyncer::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->merchant_center    = $this->createMock( MerchantCenterService::class );
		$this->product_helper     = $this->createMock( ProductHelper::class );

		$this->job = new CleanupOrphanedMarketProductsJob(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->merchant_center,
			$this->product_helper
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_throws_when_feed_labels_missing() {
		$this->expectException( InvalidValue::class );

		$this->job->schedule();
	}

	public function test_schedule_throws_when_feed_labels_empty() {
		$this->expectException( InvalidValue::class );

		$this->job->schedule( [ 'feed_labels' => [] ] );
	}

	public function test_schedule_throws_when_feed_labels_not_an_array() {
		$this->expectException( InvalidValue::class );

		$this->job->schedule( [ 'feed_labels' => 'GB' ] );
	}

	public function test_schedule_schedules_immediate_with_feed_labels() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK, [ [ 'feed_labels' => [ 'GB', 'GB-CY' ] ] ] );

		$this->job->schedule( [ 'feed_labels' => [ 'GB', 'GB-CY' ] ] );
	}

	public function test_process_items_builds_request_entry_per_product_for_feed_label() {
		$product   = WC_Helper_Product::create_simple_product();
		$google_id = 'online:en:GB:gla_' . $product->get_id();
		$other_id  = 'online:en:US:gla_' . $product->get_id();

		$this->product_repository->method( 'find_synced_product_ids' )
			->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )
			->with( [ $product->get_id() ] )
			->willReturn( [ $product ] );

		$this->product_helper->method( 'get_synced_google_product_ids' )
			->with( $product )
			->willReturn(
				[
					'US' => $other_id,
					'GB' => $google_id,
				]
			);

		$this->product_syncer->expects( $this->once() )
			->method( 'delete_by_batch_requests' )
			->with(
				$this->callback(
					function ( $entries ) use ( $product, $google_id ) {
						if ( ! is_array( $entries ) || 1 !== count( $entries ) ) {
							return false;
						}
						$entry = array_values( $entries )[0];
						return $entry instanceof BatchProductIDRequestEntry
							&& $entry->get_wc_product_id() === $product->get_id()
							&& $entry->get_product_id() === $google_id;
					}
				)
			)
			->willReturn( new BatchProductResponse( [], [] ) );

		do_action( self::PROCESS_ITEM_HOOK, [ 'feed_labels' => [ 'GB' ] ] );
	}

	public function test_process_items_builds_request_entries_for_every_given_feed_label() {
		$product      = WC_Helper_Product::create_simple_product();
		$bare_id      = 'online:en:GB:gla_' . $product->get_id();
		$language_id  = 'online:cy:GB-CY:gla_' . $product->get_id();
		$unrelated_id = 'online:en:US:gla_' . $product->get_id();

		$this->product_repository->method( 'find_synced_product_ids' )
			->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )
			->willReturn( [ $product ] );

		$this->product_helper->method( 'get_synced_google_product_ids' )
			->willReturn(
				[
					'US'    => $unrelated_id,
					'GB'    => $bare_id,
					'GB-CY' => $language_id,
				]
			);

		$this->product_syncer->expects( $this->once() )
			->method( 'delete_by_batch_requests' )
			->with(
				$this->callback(
					function ( $entries ) use ( $bare_id, $language_id ) {
						$requested_ids = array_map(
							function ( BatchProductIDRequestEntry $entry ) {
								return $entry->get_product_id();
							},
							array_values( $entries )
						);
						sort( $requested_ids );
						$expected = [ $bare_id, $language_id ];
						sort( $expected );

						return $requested_ids === $expected;
					}
				)
			)
			->willReturn( new BatchProductResponse( [], [] ) );

		do_action( self::PROCESS_ITEM_HOOK, [ 'feed_labels' => [ 'GB', 'GB-CY' ] ] );
	}

	public function test_process_items_leaves_local_meta_intact_on_api_failure() {
		$product   = WC_Helper_Product::create_simple_product();
		$google_id = 'online:en:GB:gla_' . $product->get_id();

		$this->product_repository->method( 'find_synced_product_ids' )
			->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )
			->willReturn( [ $product ] );

		$this->product_helper->method( 'get_synced_google_product_ids' )
			->willReturn( [ 'GB' => $google_id ] );

		// API returned no successfully-deleted products.
		$this->product_syncer->method( 'delete_by_batch_requests' )
			->willReturn( new BatchProductResponse( [], [ 'error' => 'boom' ] ) );

		$this->product_helper->expects( $this->never() )
			->method( 'remove_google_id' );

		do_action( self::PROCESS_ITEM_HOOK, [ 'feed_labels' => [ 'GB' ] ] );
	}

	public function test_process_items_skips_products_with_no_entry_for_feed_label() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_synced_product_ids' )
			->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )
			->willReturn( [ $product ] );

		$this->product_helper->method( 'get_synced_google_product_ids' )
			->willReturn(
				[
					'US' => 'online:en:US:gla_' . $product->get_id(),
				]
			);

		$this->product_syncer->expects( $this->never() )
			->method( 'delete_by_batch_requests' );

		do_action( self::PROCESS_ITEM_HOOK, [ 'feed_labels' => [ 'GB' ] ] );
	}

	public function test_process_items_returns_early_when_no_synced_products() {
		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [] );

		$this->product_syncer->expects( $this->never() )
			->method( 'delete_by_batch_requests' );

		do_action( self::PROCESS_ITEM_HOOK, [ 'feed_labels' => [ 'GB' ] ] );
	}
}
