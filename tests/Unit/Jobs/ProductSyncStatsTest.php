<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use ActionScheduler_Action;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductSyncStatsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ProductSyncStatsTest extends UnitTest {

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var ProductSyncStats $sync_stats */
	protected $sync_stats;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionScheduler::class );
		$this->sync_stats       = new ProductSyncStats( $this->action_scheduler );
	}

	public function test_get_count_counts_pending_product_sync_jobs() {
		$this->mock_pending_hooks(
			[
				'gla/jobs/update_products/process_item',
				'gla/jobs/update_all_products/create_batch',
				'gla/jobs/delete_products/process_item',
				'gla/jobs/update_merchant_product_statuses/process_item',
			]
		);

		$this->assertSame( 3, $this->sync_stats->get_count() );
	}

	public function test_get_count_counts_a_pending_queued_updates_run() {
		$this->mock_pending_hooks( [ 'gla/jobs/process_queued_product_updates/process_item' ] );

		$this->assertSame( 1, $this->sync_stats->get_count() );
	}

	public function test_get_count_ignores_the_queued_updates_daily_start() {
		$this->mock_pending_hooks( [ 'gla/jobs/process_queued_product_updates/start' ] );

		$this->assertSame( 0, $this->sync_stats->get_count() );
	}

	/**
	 * @param string[] $hooks
	 */
	protected function mock_pending_hooks( array $hooks ): void {
		$this->action_scheduler->expects( $this->once() )
			->method( 'search' )
			->with(
				[
					'status'   => 'pending',
					'per_page' => -1,
				]
			)
			->willReturn(
				array_map(
					function ( string $hook ) {
						return new ActionScheduler_Action( $hook );
					},
					$hooks
				)
			);
	}
}
