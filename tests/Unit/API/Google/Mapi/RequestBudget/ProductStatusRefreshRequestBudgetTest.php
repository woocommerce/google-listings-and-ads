<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\RequestBudget;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiProductsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantProductStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductStatusRefreshRequestBudgetTest
 *
 * Fixed request-count regression suite for the Merchant Center status refresh job.
 *
 * This is a direct regression test for the incident that prompted this suite: before
 * PR #3779, `UpdateMerchantProductStatuses` paged the product_view report and then
 * called `products.get` once per synced product to read its item-level issues, so a
 * refresh of N products cost N extra HTTP requests. That per-product read was ~87% of
 * all Merchant API traffic fleet-wide (a 4.8x increase over the pre-migration
 * baseline) before it was fixed by driving the refresh from `products.list` pages
 * instead. These tests run the job to completion across a full multi-page refresh
 * (following its own self-rescheduling synchronously, the way separate Action
 * Scheduler runs would in production) and assert the total number of `list_page()`
 * calls stays at ceil(N / page_size) - and that `get()`/`get_many()` are never called
 * - so a future change that reintroduces per-product reads here fails a test instead
 * of reaching production traffic.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\RequestBudget
 */
class ProductStatusRefreshRequestBudgetTest extends UnitTest {

	protected const PROCESS_ITEM_HOOK = 'gla/jobs/update_merchant_product_statuses/process_item';

	/** @var MockObject|ActionSchedulerInterface */
	protected $action_scheduler;

	/** @var MockObject|MapiProductsService */
	protected $mapi_products;

	/** @var MockObject|MerchantStatuses */
	protected $merchant_statuses;

	/** @var UpdateMerchantProductStatuses */
	protected $job;

	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );

		$monitor = $this->createMock( ActionSchedulerJobMonitor::class );

		$merchant_center_service = $this->createMock( MerchantCenterService::class );
		$merchant_center_service->method( 'is_connected' )->willReturn( true );

		$this->mapi_products     = $this->createMock( MapiProductsService::class );
		$this->merchant_statuses = $this->createMock( MerchantStatuses::class );

		$this->job = new UpdateMerchantProductStatuses(
			$this->action_scheduler,
			$monitor,
			$merchant_center_service,
			$this->mapi_products,
			$this->merchant_statuses
		);
		$this->job->init();

		// Drive the job's own self-rescheduling synchronously, so a full multi-page
		// refresh runs to completion inside one test the same way separate Action
		// Scheduler invocations would carry the page token across in production.
		$this->action_scheduler->method( 'schedule_immediate' )
			->willReturnCallback(
				function ( $hook, $args ) {
					do_action( $hook, $args[0] ?? [] );
				}
			);
	}

	/**
	 * @return array<string, array{0: int, 1: int}>
	 */
	public function budget_sizes(): array {
		return [
			'a single page'            => [ 500, 500 ],
			'a partial second page'    => [ 600, 500 ],
			'exactly two pages'        => [ 1000, 500 ],
			'a ten thousand catalog'   => [ 10000, 500 ],
			'max API page size in use' => [ 10000, 1000 ],
		];
	}

	/**
	 * @dataProvider budget_sizes
	 *
	 * @param int $product_count Size of the simulated catalog.
	 * @param int $page_size     Products per list_page() page.
	 */
	public function test_list_page_call_count_scales_as_ceil_n_over_page_size( int $product_count, int $page_size ): void {
		add_filter( 'woocommerce_gla_product_view_report_page_size', static fn() => $page_size );

		$pages          = $this->pages_for( $product_count, $page_size );
		$expected_pages = count( $pages );

		$matcher = $this->exactly( $expected_pages );
		$this->mapi_products->expects( $matcher )
			->method( 'list_page' )
			->willReturnCallback(
				function () use ( $matcher, $pages ) {
					return $pages[ $matcher->getInvocationCount() - 1 ];
				}
			);

		// The actual incident: the refresh fell back to one products.get per product.
		// A regression back to that shape must fail here, not in production traffic.
		$this->mapi_products->expects( $this->never() )->method( 'get' );
		$this->mapi_products->expects( $this->never() )->method( 'get_many' );

		$this->merchant_statuses->expects( $this->once() )->method( 'handle_complete_mc_statuses_fetching' );

		$this->job->schedule();

		remove_all_filters( 'woocommerce_gla_product_view_report_page_size' );
	}

	/**
	 * Build the sequence of list_page() page responses a catalog of $count products
	 * would paginate through at $page_size per page. The pages carry no product
	 * payload: this suite budgets the *number* of requests a refresh costs, not the
	 * per-product data flowing through them (already covered by
	 * MapiProductsServiceTest and UpdateMerchantProductStatusesTest).
	 *
	 * @param int $count     Size of the simulated catalog.
	 * @param int $page_size Products per page.
	 *
	 * @return array<int, array{products: array, next_page_token: ?string}>
	 */
	protected function pages_for( int $count, int $page_size ): array {
		$page_count = max( 1, (int) ceil( $count / $page_size ) );

		$pages = [];
		for ( $i = 0; $i < $page_count; $i++ ) {
			$pages[] = [
				'products'        => [],
				'next_page_token' => $i < $page_count - 1 ? 'token_' . $i : null,
			];
		}

		return $pages;
	}
}
