<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupNotFoundProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CleanupNotFoundProductsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 *
 * @property MockObject|ActionScheduler           $action_scheduler
 * @property MockObject|ActionSchedulerJobMonitor $monitor
 * @property MockObject|OptionsInterface          $options
 * @property MockObject|ProductHelper             $product_helper
 * @property CleanupNotFoundProducts              $job
 */
class CleanupNotFoundProductsTest extends UnitTest {
	protected const JOB_NAME          = 'cleanup_not_found_products';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';
	protected const BATCH_SIZE        = 100;

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_no_ids() {
		$this->expectException( JobException::class );
		$this->job->schedule();
	}

	public function test_schedule_ids() {
		$ids = [ 'online:en:US:gla_1', 'online:en:US:gla_2' ];

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with( OptionsInterface::GOOGLE_IDS_TO_CLEANUP, $ids, false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::CREATE_BATCH_HOOK, [ 1 ] );

		$this->job->schedule( [ $ids ] );
	}

	public function test_schedule_one_batch() {
		$ids = [ 'online:en:US:gla_1', 'online:en:US:gla_2' ];

		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::GOOGLE_IDS_TO_CLEANUP )
			->willReturn( $ids );

		$this->action_scheduler->expects( $this->exactly( 2 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $ids ] ]
			);

		$this->options->expects( $this->once() )
			->method( 'delete' )
			->with( OptionsInterface::GOOGLE_IDS_TO_CLEANUP );

		$this->job->schedule( [ $ids ] );

		// Trigger first batch
		do_action( self::CREATE_BATCH_HOOK, 1 );
	}

	public function test_schedule_multiple_batches() {
		$ids = [];
		foreach ( range( 1, self::BATCH_SIZE * 5 ) as $id ) {
			$ids[] = "online:en:US:gla_{$id}";
		}

		$first_batch  = array_slice( $ids, 0, self::BATCH_SIZE );
		$second_batch = array_slice( $ids, self::BATCH_SIZE, self::BATCH_SIZE );

		$this->options->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->with( OptionsInterface::GOOGLE_IDS_TO_CLEANUP )
			->willReturn( $ids );

		$this->action_scheduler->expects( $this->exactly( 5 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $first_batch ] ],
				[ self::CREATE_BATCH_HOOK, [ 2 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $second_batch ] ],
				[ self::CREATE_BATCH_HOOK, [ 3 ] ]
			);

		$this->job->schedule( [ $ids ] );

		// Trigger first two batches
		do_action( self::CREATE_BATCH_HOOK, 1 );
		do_action( self::CREATE_BATCH_HOOK, 2 );
	}

	public function test_process_items() {
		$ids = [ 'online:en:US:gla_1', 'online:en:US:gla_2' ];

		$this->product_helper->expects( $this->exactly( 2 ) )
			->method( 'remove_by_google_id' )
			->withConsecutive(
				[ 'online:en:US:gla_1' ],
				[ 'online:en:US:gla_2' ]
			);

		do_action( self::PROCESS_ITEM_HOOK, $ids );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionScheduler::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->options          = $this->createMock( OptionsInterface::class );
		$this->product_helper   = $this->createMock( ProductHelper::class );
		$this->job              = new CleanupNotFoundProducts( $this->action_scheduler, $this->monitor, $this->product_helper );

		$this->job->set_options_object( $this->options );
		$this->job->init();
	}
}
