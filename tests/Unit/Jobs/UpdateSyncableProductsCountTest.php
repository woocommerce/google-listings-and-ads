<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateSyncableProductsCount;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateSyncableProductsCountTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 *
 * @property MockObject|ActionScheduler           $action_scheduler
 * @property MockObject|ActionSchedulerJobMonitor $monitor
 * @property MockObject|ProductHelper             $product_helper
 * @property MockObject|ProductRepository         $product_repository
 * @property MockObject|OptionsInterface          $options
 * @property UpdateSyncableProductsCount          $job
 */
class UpdateSyncableProductsCountTest extends UnitTest {

	use ProductTrait;

	protected const BATCH_SIZE        = 2;
	protected const JOB_NAME          = 'update_syncable_products_count';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_helper     = $this->createMock( ProductHelper::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->options            = $this->createMock( OptionsInterface::class );
		$this->job                = new UpdateSyncableProductsCount(
			$this->action_scheduler,
			$this->monitor,
			$this->product_repository,
			$this->product_helper
		);
		$this->job->set_options_object( $this->options );

		/* adding a filter to make batch smaller for testing */
		add_filter(
			'woocommerce_gla_batched_job_size',
			function ( $batch_count, $job_name ) {
				if ( self::JOB_NAME === $job_name ) {
					return self::BATCH_SIZE;
				}
				return $batch_count;
			},
			10,
			2
		);

		$this->job->init();
	}

	public function test_update_syncable_products_count() {
		// Syncable products count: 2, total products count: 2
		$batch_a = new FilteredProductList( $this->generate_simple_product_mocks_set( 2 ), 2 );

		// Syncable products count: 2, total products count: 2
		$batch_b = new FilteredProductList( $this->generate_simple_product_mocks_set( 2 ), 2 );

		// Syncable products count: 0, total products count: 0
		$batch_c = new FilteredProductList( [], 0 );

		$syncable_products = [ ...$batch_a->get_product_ids(), ...$batch_b->get_product_ids() ];

		$this->action_scheduler->expects( $this->exactly( 5 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $batch_a->get_product_ids() ] ],
				[ self::CREATE_BATCH_HOOK, [ 2 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $batch_b->get_product_ids() ] ],
				[ self::CREATE_BATCH_HOOK, [ 3 ] ],
			);

		$this->product_repository->expects( $this->exactly( 3 ) )
			->method( 'find_sync_ready_products' )
			->withConsecutive( [ [], self::BATCH_SIZE, 0 ], [ [], self::BATCH_SIZE, 2 ], [ [], self::BATCH_SIZE, 4 ] )
			->willReturnOnConsecutiveCalls( $batch_a, $batch_b, $batch_c );

		$this->product_helper->expects( $this->exactly( 2 ) )
			->method( 'maybe_swap_for_parent_ids' )
			->withConsecutive( [ $batch_a->get_product_ids() ], [ $batch_b->get_product_ids() ] )
			->willReturnOnConsecutiveCalls( $batch_a->get_product_ids(), $batch_b->get_product_ids() );

		$this->options->expects( $this->exactly( 3 ) )
			->method( 'get' )
			->with( OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA )
			->willReturnOnConsecutiveCalls(
				null,
				$batch_a->get_product_ids(),
				$syncable_products
			);

		$this->options->expects( $this->exactly( 3 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA, $batch_a->get_product_ids() ],
				[ OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA, $syncable_products ],
				[ OptionsInterface::SYNCABLE_PRODUCTS_COUNT, 4 ]
			);

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
		do_action( self::PROCESS_ITEM_HOOK, $batch_a->get_product_ids() );
		do_action( self::CREATE_BATCH_HOOK, 2 );
		do_action( self::PROCESS_ITEM_HOOK, $batch_b->get_product_ids() );
		do_action( self::CREATE_BATCH_HOOK, 3 );
	}

	public function test_update_syncable_products_count_duplicate_product_ids() {
		// syncable products count: 2, total products count: 2
		$batch_a = new filteredproductlist(
			[
				$this->generate_simple_product_mock( 12345 ),
				$this->generate_simple_product_mock( 12345 ),
			],
			2
		);

		// Syncable products count: 2, total products count: 2
		$batch_b = new filteredproductlist(
			[
				$this->generate_simple_product_mock( 23456 ),
				$this->generate_simple_product_mock( 23456 ),
			],
			2
		);

		// Syncable products count: 0, total products count: 0
		$batch_c = new FilteredProductList( [], 0 );

		$this->action_scheduler->expects( $this->exactly( 5 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $batch_a->get_product_ids() ] ],
				[ self::CREATE_BATCH_HOOK, [ 2 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $batch_b->get_product_ids() ] ],
				[ self::CREATE_BATCH_HOOK, [ 3 ] ],
			);

		$this->product_repository->expects( $this->exactly( 3 ) )
			->method( 'find_sync_ready_products' )
			->withConsecutive( [ [], self::BATCH_SIZE, 0 ], [ [], self::BATCH_SIZE, 2 ], [ [], self::BATCH_SIZE, 4 ] )
			->willReturnOnConsecutiveCalls( $batch_a, $batch_b, $batch_c );

		$this->product_helper->expects( $this->exactly( 2 ) )
			->method( 'maybe_swap_for_parent_ids' )
			->withConsecutive( [ $batch_a->get_product_ids() ], [ $batch_b->get_product_ids() ] )
			->willReturnOnConsecutiveCalls( $batch_a->get_product_ids(), $batch_b->get_product_ids() );

		$this->options->expects( $this->exactly( 3 ) )
			->method( 'get' )
			->with( OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA )
			->willReturnOnConsecutiveCalls(
				null,
				[ 12345 ],
				[ 12345, 23456 ]
			);

		$this->options->expects( $this->exactly( 3 ) )
			->method( 'update' )
			->withConsecutive(
				[ OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA, [ 12345 ] ],
				[ OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA, [ 12345, 23456 ] ],
				[ OptionsInterface::SYNCABLE_PRODUCTS_COUNT, 2 ]
			);

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
		do_action( self::PROCESS_ITEM_HOOK, $batch_a->get_product_ids() );
		do_action( self::CREATE_BATCH_HOOK, 2 );
		do_action( self::PROCESS_ITEM_HOOK, $batch_b->get_product_ids() );
		do_action( self::CREATE_BATCH_HOOK, 3 );
	}

	public function test_update_syncable_products_count_no_syncable_products_single_batch() {
		// Syncable products count: 0, total products count: 0
		$batch = new FilteredProductList( [], 0 );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::CREATE_BATCH_HOOK, [ 1 ] );

		$this->product_repository->expects( $this->once() )
			->method( 'find_sync_ready_products' )
			->with( [], self::BATCH_SIZE, 0 )
			->willReturn( $batch );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA )
			->willReturn( null );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SYNCABLE_PRODUCTS_COUNT, 0 );

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
	}

	public function test_update_syncable_products_count_no_syncable_products_multiple_batches() {
		// Syncable products count: 0, total products count: 4
		$batch_a = new FilteredProductList( [], 4 );

		// Syncable products count: 0, total products count: 2
		$batch_b = new FilteredProductList( [], 2 );

		// Syncable products count: 0, total products count: 0
		$batch_c = new FilteredProductList( [], 0 );

		$this->action_scheduler->expects( $this->exactly( 3 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::CREATE_BATCH_HOOK, [ 2 ] ],
				[ self::CREATE_BATCH_HOOK, [ 3 ] ]
			);

		$this->product_repository->expects( $this->exactly( 3 ) )
			->method( 'find_sync_ready_products' )
			->withConsecutive(
				[ [], self::BATCH_SIZE, 0 ],
				[ [], self::BATCH_SIZE, 2 ],
				[ [], self::BATCH_SIZE, 4 ],
			)
			->willReturnOnConsecutiveCalls( $batch_a, $batch_b, $batch_c );

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA )
			->willReturn( null );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::SYNCABLE_PRODUCTS_COUNT, 0 );

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
		do_action( self::CREATE_BATCH_HOOK, 2 );
		do_action( self::CREATE_BATCH_HOOK, 3 );
	}
}
