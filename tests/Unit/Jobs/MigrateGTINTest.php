<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\MigrateGTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class MigrateGTINTest
 *
 * @see MigrateGTIN
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class MigrateGTINTest extends UnitTest {
	use ProductTrait;

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|AttributeManager $attribute_manager */
	protected $attribute_manager;

	/** @var MockObject|ProductRepository $product_repository */
	protected $product_repository;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MigrateGTIN $job */
	protected $job;

	protected const JOB_NAME          = 'migrate_gtin';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionScheduler::class );
		$this->options            = $this->createMock( OptionsInterface::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->attribute_manager  = $this->createMock( AttributeManager::class );
		$this->job                = new MigrateGTIN(
			$this->action_scheduler,
			$this->monitor,
			$this->product_repository,
			$this->attribute_manager
		);

		// reduce batch size for testing
		add_filter(
			'woocommerce_gla_batched_job_size',
			function ( $batch_count, $job_name ) {
				if ( self::JOB_NAME === $job_name ) {
					return 3;
				}
				return $batch_count;
			},
			10,
			2
		);

		$this->job->set_options_object( $this->options );
		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_updates_status_to_started() {
		$this->options->expects( $this->exactly( 1 ) )
			->method( 'update' )
			->with( OptionsInterface::GTIN_MIGRATION_STATUS, MigrateGTIN::GTIN_MIGRATION_STARTED );
		$this->job->schedule();
	}

	public function test_completed_job_updates_status_to_completed() {
		$this->options->expects( $this->exactly( 1 ) )
			->method( 'update' )
			->with( OptionsInterface::GTIN_MIGRATION_STATUS, MigrateGTIN::GTIN_MIGRATION_COMPLETED );
		$this->job->handle_complete( 2 );
	}

	public function test_single_empty_batch() {
		$this->product_repository->expects( $this->once() )
			->method( 'find_all_product_ids' )
			->willReturn( [] );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::CREATE_BATCH_HOOK, [ 1 ] );

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
	}

	public function test_single_batch() {
		$batch = [ 1, 2, 3 ];

		$this->product_repository->expects( $this->once() )
			->method( 'find_all_product_ids' )
			->willReturn( $batch );

		$this->action_scheduler->expects( $this->exactly( 3 ) )
			->method( 'has_scheduled_action' )->willReturn( false );

		$this->action_scheduler->expects( $this->exactly( 3 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $batch ] ]
			);

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
	}

	public function test_multiple_batches() {
		$batch_one   = [ 1, 2, 3 ];
		$batch_two   = [ 4, 5, 6 ];
		$batch_three = [];

		$this->action_scheduler->expects( $this->exactly( 5 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $batch_one ] ],
				[ self::CREATE_BATCH_HOOK, [ 2 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $batch_two ] ],
				[ self::CREATE_BATCH_HOOK, [ 3 ] ],
			);

		$this->product_repository->expects( $this->exactly( 3 ) )
			->method( 'find_all_product_ids' )
			->withConsecutive( [ 3, 0 ], [ 3, 3 ], [ 3, 6 ] )
			->willReturnOnConsecutiveCalls( $batch_one, $batch_two, $batch_three );

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
		do_action( self::CREATE_BATCH_HOOK, 2 );
		do_action( self::CREATE_BATCH_HOOK, 3 );
	}

	public function test_process_item_is_called_by_hook() {
		$this->product_repository
			->expects( $this->once() )
			->method( 'find_by_ids' )
			->with( [ 1 ] );

		do_action( self::PROCESS_ITEM_HOOK, [ 1 ] );
	}

	public function test_process_items_successfully_updates_gtin() {
		$product = WC_Helper_Product::create_simple_product();
		$this->product_repository
			->expects( $this->once() )
			->method( 'find_by_ids' )
			->with( [ 1 ] )
			->willReturn( [ $product ] );

		$this->attribute_manager
			->expects( $this->once() )
			->method( 'get_value' )
			->with( $product, 'gtin' )
			->willReturn( '1234-5678' );

		$this->assertEquals( $product->get_global_unique_id(), null );
		$this->job->handle_process_items_action( [ 1 ] );
		$this->assertEquals( $product->get_global_unique_id(), '12345678' );
	}

	public function test_process_items_not_updates_gtin_if_not_found() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository
			->expects( $this->once() )
			->method( 'find_by_ids' )
			->with( [ 1 ] )
			->willReturn( [ $product ] );

		$this->attribute_manager
			->expects( $this->once() )
			->method( 'get_value' )
			->with( $product, 'gtin' )
			->willReturn( null );

		$this->assertEquals( $product->get_global_unique_id(), null );
		$this->job->handle_process_items_action( [ 1 ] );
		$this->assertEquals( $product->get_global_unique_id(), null );
	}

	public function test_process_items_not_updates_gtin_if_already_set() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();
		$product->set_global_unique_id( '11112222' );
		$product->save();

		$this->product_repository
			->expects( $this->once() )
			->method( 'find_by_ids' )
			->with( [ 1 ] )
			->willReturn( [ $product ] );

		$this->attribute_manager
			->expects( $this->never() )
			->method( 'get_value' );

		$this->assertEquals( $product->get_global_unique_id(), '11112222' );
		$this->job->handle_process_items_action( [ 1 ] );
		$this->assertEquals( $product->get_global_unique_id(), '11112222' );
	}

	public function test_process_items_not_updates_gtin_if_not_a_number() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository
			->expects( $this->once() )
			->method( 'find_by_ids' )
			->with( [ 1 ] )
			->willReturn( [ $product ] );

		$this->attribute_manager
			->expects( $this->once() )
			->method( 'get_value' )
			->with( $product, 'gtin' )
			->willReturn( 'not a number' );

		$this->assertEquals( $product->get_global_unique_id(), null );
		$this->job->handle_process_items_action( [ 1 ] );
		$this->assertEquals( $product->get_global_unique_id(), null );
	}
}
