<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\JobTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DeleteAllProductsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class DeleteAllProductsTest extends UnitTest {

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

	/** @var MockObject|BatchProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var DeleteAllProducts $job */
	protected $job;

	/**
	 * @var MerchantStatuses|MockObject
	 */
	protected $merchant_statuses;

	protected const JOB_NAME          = 'delete_all_products';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer     = $this->createMock( ProductSyncer::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->product_helper     = $this->createMock( BatchProductHelper::class );
		$this->merchant_center    = $this->createMock( MerchantCenterService::class );
		$this->merchant_statuses  = $this->createMock( MerchantStatuses::class );
		$this->job                = new DeleteAllProducts(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->product_helper,
			$this->merchant_center,
			$this->merchant_statuses
		);

		$this->merchant_center
			->method( 'is_ready_for_syncing' )
			->willReturn( true );

		/* adding a filter to make batch smaller for testing */
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

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	/**
	 * Test single empty batch and calls handle_complete
	 */
	public function test_single_empty_batch() {
		$this->product_repository->expects( $this->once() )
			->method( 'find_synced_product_ids' )
			->willReturn( [] );

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->merchant_statuses->expects( $this->once() )
			->method( 'maybe_refresh_status_data' )
			->with( true );

		do_action( self::CREATE_BATCH_HOOK, 1 );
	}

	public function test_process_item() {
		$filtered_product_list = new FilteredProductList( $this->generate_simple_product_mocks_set( 1 ), 1 );
		$request_entries       = [
			new BatchProductIDRequestEntry(
				$filtered_product_list->get()[0]->get_id(),
				'mc_id'
			),
		];

		$this->product_repository
			->expects( $this->once() )
			->method( 'find_by_ids' )
			->with( $filtered_product_list->get_product_ids() )
			->willReturn( $filtered_product_list->get() );

		$this->product_helper->expects( $this->once() )
			->method( 'generate_delete_request_entries' )
			->with( $filtered_product_list->get() )
			->willReturn(
				$request_entries
			);

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->product_syncer
			->expects( $this->once() )
			->method( 'delete_by_batch_requests' )
			->with( $request_entries );

		do_action( self::PROCESS_ITEM_HOOK, $filtered_product_list->get_product_ids() );
	}
}
