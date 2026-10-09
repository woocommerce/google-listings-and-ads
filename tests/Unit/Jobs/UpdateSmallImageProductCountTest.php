<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateSmallImageProductCount;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateSmallImageProductCountTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateSmallImageProductCountTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|ProductImageSizeAudit $image_size_audit */
	protected $image_size_audit;

	/** @var UpdateSmallImageProductCount $job */
	protected $job;

	protected const PROCESS_ITEM_HOOK = 'gla/jobs/update_small_image_product_count/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->image_size_audit = $this->createMock( ProductImageSizeAudit::class );

		$this->job = new UpdateSmallImageProductCount(
			$this->action_scheduler,
			$this->monitor,
			$this->image_size_audit
		);
	}

	public function test_get_name() {
		$this->assertEquals( 'update_small_image_product_count', $this->job->get_name() );
	}

	public function test_schedule_queues_process_item() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK );

		$this->job->schedule();
	}

	public function test_schedule_skips_when_already_pending() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( true );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_process_items_refreshes_count() {
		$this->image_size_audit->expects( $this->once() )
			->method( 'refresh_small_image_product_count' );

		$this->job->handle_process_items_action( [] );
	}
}
