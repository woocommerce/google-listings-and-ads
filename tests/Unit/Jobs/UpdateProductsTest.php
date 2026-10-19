<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\JobTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class UpdateProductsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateProductsTest extends UnitTest {

	use ProductTrait;
	use JobTrait;

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

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var UpdateProducts $job */
	protected $job;

	protected const JOB_NAME          = 'update_products';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionScheduler::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer     = $this->createMock( ProductSyncer::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->merchant_center    = $this->createMock( MerchantCenterService::class );
		$this->job                = new UpdateProducts(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->merchant_center
		);
		$this->options            = $this->createMock( OptionsInterface::class );
		$this->job->set_options_object( $this->options );

		$this->job->init();
		$this->add_job_start_hook();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_throws_exception_no_args() {
		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Array of WooCommerce Product IDs' );

		$this->job->schedule();
	}

	public function test_schedule_schedules_single_job() {
		$ids = [ 1, 2 ];
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_single' )
			->with(
				$this->greaterThan( 0 ),
				self::PROCESS_ITEM_HOOK,
				[ $ids ]
			);

		do_action( 'woocommerce_gla_batch_retry_update_products', $ids );
	}

	public function test_schedule_schedules_immediate_job() {
		$ids = [ 1, 2 ];
		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->merchant_center
			->method( 'is_ready_for_syncing' )
			->willReturn( true );
		$this->merchant_center
			->method( 'should_push' )
			->willReturn( true );
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with(
				self::PROCESS_ITEM_HOOK,
				[ $ids ]
			);

		$this->job->schedule( [ $ids ] );
	}

	public function test_process_items_no_products() {
		$product_list = $this->createMock( FilteredProductList::class );
		$product_list->method( 'get' )->willReturn( [] );

		$this->product_repository->method( 'find_sync_ready_products' )->willReturn( $product_list );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Job item not found' );

		$this->job->process_items( [] );
	}

	public function test_process_items() {
		$products = [
			WC_Helper_Product::create_simple_product(),
			WC_Helper_Product::create_simple_product(),
		];

		$product_list = $this->createMock( FilteredProductList::class );
		$product_list->method( 'get' )->willReturn( $products );

		$this->product_repository->method( 'find_sync_ready_products' )->willReturn( $product_list );

		$this->product_syncer->expects( $this->once() )->method( 'update' )->with( $products );
		// Nothing reached Google, so the last product sync time is left alone.
		$this->options->expects( $this->never() )->method( 'update' );

		$this->job->process_items( [] );
	}

	/**
	 * @dataProvider last_product_sync_ages
	 *
	 * @param int|null $stored_ago Seconds since the stored last product sync, null when none.
	 * @param bool     $writes     Whether a push that reached Google rewrites it.
	 */
	public function test_process_items_records_last_product_sync_at_most_once_a_minute( ?int $stored_ago, bool $writes ) {
		$product      = WC_Helper_Product::create_simple_product();
		$product_list = $this->createMock( FilteredProductList::class );
		$product_list->method( 'get' )->willReturn( [ $product ] );
		$this->product_repository->method( 'find_sync_ready_products' )->willReturn( $product_list );

		$this->product_syncer->method( 'update' )->willReturn(
			new BatchProductResponse( [ new BatchProductEntry( $product->get_id() ) ], [] )
		);
		$this->options->method( 'get' )
			->with( OptionsInterface::PRODUCT_SYNC_LAST_SUCCESS )
			->willReturn( null === $stored_ago ? null : time() - $stored_ago );
		$this->options->expects( $writes ? $this->once() : $this->never() )
			->method( 'update' )
			->with( OptionsInterface::PRODUCT_SYNC_LAST_SUCCESS, $this->isType( 'int' ) );

		$this->job->process_items( [ $product->get_id() ] );
	}

	/**
	 * @return array
	 */
	public function last_product_sync_ages(): array {
		return [
			'never recorded'         => [ null, true ],
			'30 seconds ago'         => [ 30, false ],
			'two minutes ago'        => [ 120, true ],
			'in the future (skewed)' => [ -HOUR_IN_SECONDS, true ],
		];
	}
}
