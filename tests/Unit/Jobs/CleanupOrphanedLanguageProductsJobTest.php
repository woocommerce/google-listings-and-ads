<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductResponse;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupOrphanedLanguageProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class CleanupOrphanedLanguageProductsJobTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class CleanupOrphanedLanguageProductsJobTest extends UnitTest {

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

	/** @var MockObject|WPML $wpml */
	protected $wpml;

	/** @var CleanupOrphanedLanguageProductsJob $job */
	protected $job;

	protected const JOB_NAME          = 'cleanup_orphaned_language_products_job';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionScheduler::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer     = $this->createMock( ProductSyncer::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->merchant_center    = $this->createMock( MerchantCenterService::class );
		$this->product_helper     = $this->createMock( ProductHelper::class );
		$this->wpml               = $this->createMock( WPML::class );

		$this->job = new CleanupOrphanedLanguageProductsJob(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->merchant_center,
			$this->product_helper,
			$this->wpml
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_throws_when_keys_missing() {
		$this->expectException( InvalidValue::class );
		$this->job->schedule( [ 'removed_languages' => [ 'fr' ] ] );
	}

	public function test_schedule_throws_when_keys_empty() {
		$this->expectException( InvalidValue::class );
		$this->job->schedule(
			[
				'keys'              => [],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_schedule_throws_when_removed_languages_missing() {
		$this->expectException( InvalidValue::class );
		$this->job->schedule( [ 'keys' => [ 'GB' ] ] );
	}

	public function test_schedule_throws_when_removed_languages_empty() {
		$this->expectException( InvalidValue::class );
		$this->job->schedule(
			[
				'keys'              => [ 'GB' ],
				'removed_languages' => [],
			]
		);
	}

	public function test_schedule_schedules_immediate_with_payload() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		$expected_payload = [
			[
				'keys'              => [ 'FR-fr' ],
				'removed_languages' => [ 'fr' ],
			],
		];

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK, $expected_payload );

		$this->job->schedule(
			[
				'keys'              => [ 'FR-fr' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_deletes_entry_for_product_in_removed_language() {
		$product   = WC_Helper_Product::create_simple_product();
		$google_id = 'online:fr:FR-fr:gla_' . $product->get_id();

		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )->willReturn( [ $product ] );

		$this->wpml->method( 'get_post_language' )->with( $product->get_id() )->willReturn( 'fr' );

		$this->product_helper->method( 'get_synced_google_product_ids' )
			->with( $product )
			->willReturn(
				[
					'FR-fr' => $google_id,
					'US'    => 'online:en:US:gla_' . $product->get_id(),
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

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'FR-fr' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_skips_products_whose_language_is_not_in_removed_set() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )->willReturn( [ $product ] );

		$this->wpml->method( 'get_post_language' )->willReturn( 'en' );

		$this->product_syncer->expects( $this->never() )->method( 'delete_by_batch_requests' );

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'FR-fr' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_skips_products_with_no_entry_under_keys() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )->willReturn( [ $product ] );

		$this->wpml->method( 'get_post_language' )->willReturn( 'fr' );

		$this->product_helper->method( 'get_synced_google_product_ids' )
			->with( $product )
			->willReturn( [ 'US' => 'online:en:US:gla_' . $product->get_id() ] );

		$this->product_syncer->expects( $this->never() )->method( 'delete_by_batch_requests' );

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'FR-fr' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_delegates_tracking_removal_to_the_delete_call() {
		$product   = WC_Helper_Product::create_simple_product();
		$google_id = 'online:fr:FR-fr:gla_' . $product->get_id();

		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )->willReturn( [ $product ] );
		$this->wpml->method( 'get_post_language' )->willReturn( 'fr' );
		$this->product_helper->method( 'get_synced_google_product_ids' )->willReturn( [ 'FR-fr' => $google_id ] );

		$google_product = $this->createMock( GoogleProduct::class );
		$google_product->method( 'getId' )->willReturn( $google_id );
		$deleted_entry = new BatchProductEntry( $product->get_id(), $google_product );

		$this->product_syncer->expects( $this->once() )
			->method( 'delete_by_batch_requests' )
			->willReturn( new BatchProductResponse( [ $deleted_entry ], [] ) );

		// The per-key removal happens inside delete_by_batch_requests(), so the
		// job itself must not touch the tracking meta again.
		$this->product_helper->expects( $this->never() )
			->method( 'remove_google_id' );

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'FR-fr' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_leaves_local_meta_intact_on_api_failure() {
		$product   = WC_Helper_Product::create_simple_product();
		$google_id = 'online:fr:FR-fr:gla_' . $product->get_id();

		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )->willReturn( [ $product ] );
		$this->wpml->method( 'get_post_language' )->willReturn( 'fr' );
		$this->product_helper->method( 'get_synced_google_product_ids' )->willReturn( [ 'FR-fr' => $google_id ] );

		$this->product_syncer->method( 'delete_by_batch_requests' )
			->willReturn( new BatchProductResponse( [], [ 'error' => 'boom' ] ) );

		$this->product_helper->expects( $this->never() )->method( 'remove_google_id' );

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'FR-fr' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_handles_multiple_keys_for_primary_market() {
		$product = WC_Helper_Product::create_simple_product();
		$gb_id   = 'online:fr:GB:gla_' . $product->get_id();
		$us_id   = 'online:fr:US:gla_' . $product->get_id();

		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )->willReturn( [ $product ] );
		$this->wpml->method( 'get_post_language' )->willReturn( 'fr' );

		$this->product_helper->method( 'get_synced_google_product_ids' )
			->with( $product )
			->willReturn(
				[
					'GB' => $gb_id,
					'US' => $us_id,
				]
			);

		$this->product_syncer->expects( $this->once() )
			->method( 'delete_by_batch_requests' )
			->with(
				$this->callback(
					function ( $entries ) use ( $gb_id, $us_id ) {
						if ( ! is_array( $entries ) || 2 !== count( $entries ) ) {
							return false;
						}
						$ids = array_map(
							static function ( $entry ) {
								return $entry->get_product_id();
							},
							array_values( $entries )
						);
						sort( $ids );
						$expected = [ $gb_id, $us_id ];
						sort( $expected );
						return $ids === $expected;
					}
				)
			)
			->willReturn( new BatchProductResponse( [], [] ) );

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'GB', 'US' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_returns_early_when_no_synced_products() {
		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [] );

		$this->product_syncer->expects( $this->never() )->method( 'delete_by_batch_requests' );

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'GB' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}

	public function test_process_items_skips_products_with_empty_wpml_language() {
		$product = WC_Helper_Product::create_simple_product();

		$this->product_repository->method( 'find_synced_product_ids' )->willReturn( [ $product->get_id() ] );
		$this->product_repository->method( 'find_by_ids' )->willReturn( [ $product ] );
		$this->wpml->method( 'get_post_language' )->willReturn( '' );

		$this->product_syncer->expects( $this->never() )->method( 'delete_by_batch_requests' );

		do_action(
			self::PROCESS_ITEM_HOOK,
			[
				'keys'              => [ 'GB' ],
				'removed_languages' => [ 'fr' ],
			]
		);
	}
}
