<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ResubmitExpiringProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ResubmitExpiringProductsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ResubmitExpiringProductsTest extends UnitTest {

	protected const CHECKPOINT_OPTION = 'woocommerce_gla_resubmit_expiring_products_checkpoint';
	protected const JOB_NAME         = 'resubmit_expiring_products';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|ProductSyncer $product_syncer */
	protected $product_syncer;

	/** @var MockObject|ProductRepository $product_repository */
	protected $product_repository;

	/** @var MockObject|BatchProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var ResubmitExpiringProducts $job */
	protected $job;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler  = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor           = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer    = $this->createMock( ProductSyncer::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->product_helper    = $this->createMock( BatchProductHelper::class );
		$this->merchant_center   = $this->createMock( MerchantCenterService::class );
		$this->merchant_statuses = $this->createMock( MerchantStatuses::class );

		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->merchant_center->method( 'is_enabled_for_datatype' )->willReturn( true );
		$this->merchant_center->method( 'should_push' )->willReturn( true );

		$this->job = new ResubmitExpiringProducts(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->product_helper,
			$this->merchant_center,
			$this->merchant_statuses
		);

		add_filter(
			'woocommerce_gla_batched_job_size',
			function ( $batch_count, $job_name ) {
				if ( self::JOB_NAME === $job_name ) {
					return 2;
				}
				return $batch_count;
			},
			10,
			2
		);

		delete_option( self::CHECKPOINT_OPTION );

		$this->job->init();
	}

	/**
	 * Runs after each test to ensure option is always cleaned up.
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( self::CHECKPOINT_OPTION );
	}

	public function test_job_name(): void {
		$this->assertSame( self::JOB_NAME, $this->job->get_name() );
	}

	/**
	 * On the first batch, no checkpoint exists yet so after_id must be 0.
	 */
	public function test_first_batch_passes_zero_as_after_id(): void {
		$this->product_repository
			->expects( $this->once() )
			->method( 'find_expiring_product_ids' )
			->with( 2, 0 )
			->willReturn( [ 10, 20 ] );

		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		do_action( self::CREATE_BATCH_HOOK, 1 );
	}

	/**
	 * After a non-empty batch, the checkpoint option must be set to the highest returned ID.
	 */
	public function test_checkpoint_is_written_after_non_empty_batch(): void {
		$this->product_repository
			->method( 'find_expiring_product_ids' )
			->willReturn( [ 10, 30, 20 ] );

		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		do_action( self::CREATE_BATCH_HOOK, 1 );

		$this->assertSame( 30, (int) get_option( self::CHECKPOINT_OPTION ) );
	}

	/**
	 * On subsequent batches, get_batch must read the stored checkpoint and pass it to the repository.
	 */
	public function test_subsequent_batch_reads_checkpoint_and_passes_it_as_after_id(): void {
		update_option( self::CHECKPOINT_OPTION, 42, false );

		$this->product_repository
			->expects( $this->once() )
			->method( 'find_expiring_product_ids' )
			->with( 2, 42 )
			->willReturn( [] );

		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		do_action( self::CREATE_BATCH_HOOK, 2 );
	}

	/**
	 * An empty batch does not overwrite the checkpoint; handle_complete fires and clears it.
	 */
	public function test_empty_batch_does_not_write_checkpoint_and_handle_complete_clears_it(): void {
		update_option( self::CHECKPOINT_OPTION, 99, false );

		$this->product_repository
			->method( 'find_expiring_product_ids' )
			->willReturn( [] );

		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		do_action( self::CREATE_BATCH_HOOK, 1 );

		$this->assertFalse( get_option( self::CHECKPOINT_OPTION ), 'Checkpoint must be deleted when the job completes.' );
	}

	/**
	 * Full two-batch cycle: batch 1 sets the checkpoint; batch 2 reads it; batch 3 is empty and clears it.
	 */
	public function test_full_keyset_pagination_cycle(): void {
		$batch_a_ids = [ 10, 20 ];
		$batch_b_ids = [ 30, 40 ];

		$this->product_repository
			->expects( $this->exactly( 3 ) )
			->method( 'find_expiring_product_ids' )
			->withConsecutive(
				[ 2, 0 ],
				[ 2, 20 ],
				[ 2, 40 ]
			)
			->willReturnOnConsecutiveCalls( $batch_a_ids, $batch_b_ids, [] );

		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		do_action( self::CREATE_BATCH_HOOK, 1 );

		$this->assertSame( 20, (int) get_option( self::CHECKPOINT_OPTION ), 'Checkpoint should be 20 after batch 1.' );

		do_action( self::CREATE_BATCH_HOOK, 2 );

		$this->assertSame( 40, (int) get_option( self::CHECKPOINT_OPTION ), 'Checkpoint should be 40 after batch 2.' );

		do_action( self::CREATE_BATCH_HOOK, 3 );

		$this->assertFalse( get_option( self::CHECKPOINT_OPTION ), 'Checkpoint must be cleared after the final empty batch.' );
	}

	/**
	 * schedule() must clear any stale checkpoint from a previous run before enqueueing the first batch.
	 */
	public function test_schedule_clears_stale_checkpoint_from_previous_run(): void {
		update_option( self::CHECKPOINT_OPTION, 77, false );

		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		$this->job->schedule();

		$this->assertFalse( get_option( self::CHECKPOINT_OPTION ), 'A stale checkpoint must be cleared when a new run is scheduled.' );
	}
}
