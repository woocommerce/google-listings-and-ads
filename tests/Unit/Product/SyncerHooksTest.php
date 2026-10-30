<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProcessQueuedProductUpdates;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\SyncerHooks;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use WC_Helper_Product;
use WC_Product;

/**
 * Class SyncerHooksTest
 *
 * @group SyncerHooks
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class SyncerHooksTest extends ContainerAwareUnitTest {

	use ProductTrait;

	/*** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var BatchProductHelper $batch_helper */
	protected $batch_helper;

	/** @var ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|UpdateProducts $update_products_job */
	protected $update_products_job;

	/** @var MockObject|DeleteProducts $delete_products_job */
	protected $delete_products_job;

	/** @var WC $wc */
	protected $wc;

	/** @var SyncerHooks $syncer_hooks */
	protected $syncer_hooks;

	/** @var MockObject|ProcessQueuedProductUpdates $queued_updates_job */
	protected $queued_updates_job;

	/** @var int $batch_size Batch size returned by the mocked update job. */
	protected $batch_size = 25;

	/** @var int $window Batch window returned by the mocked queued updates job. */
	protected $window = 0;

	public function test_create_new_simple_product_schedules_update_job() {
		$this->set_mc_and_notifications();

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' );

		WC_Helper_Product::create_simple_product( true, [ 'status' => 'publish' ] );

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_update_simple_product_schedules_update_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $product->get_id() ] ] ) );

		$product->set_status( 'publish' );
		$product->save();

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_multiple_update_events_for_same_product_only_schedules_update_job_once() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $product->get_id() ] ] ) );

		// update #1
		$product->set_status( 'publish' );
		$product->save();

		// update #2
		$product->set_description( 'Sample description' );
		$product->save();

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_create_variable_product_schedules_update_job_for_all_variations() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product( null, [ 'status' => 'draft' ] );
		$variation_ids    = $variable_product->get_children();

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ $variation_ids ] ) );

		$variable_product->set_status( 'publish' );
		$variable_product->save();

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_adding_variation_schedules_update_job() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product();

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->create_product_variation_object(
			$variable_product->get_id(),
			'DUMMY SKU VARIABLE SMALL BLUE 2',
			10,
			[
				'pa_size'   => 'small',
				'pa_colour' => 'blue',
				'pa_number' => '2',
			]
		);

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_trashing_product_does_not_schedules_delete_job_if_product_is_not_synced() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();

		$this->delete_products_job->expects( $this->never() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $product->get_id() ] ] ) );

		$product->delete();
	}

	public function test_trashing_synced_product_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		$product->delete();
	}

	public function test_force_deleting_synced_product_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		$product->delete( true );
	}

	public function test_trashing_synced_variable_schedules_delete_job_for_all_variations() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product();

		foreach ( $variable_product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );
		}

		$this->delete_products_job->expects( $this->exactly( count( $variable_product->get_children() ) ) )
			->method( 'schedule' );

		$variable_product->delete();
	}

	public function test_force_deleting_synced_variable_schedules_delete_job_for_all_variations() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product();

		foreach ( $variable_product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock() );
		}

		$this->delete_products_job->expects( $this->exactly( count( $variable_product->get_children() ) ) )
			->method( 'schedule' );

		$variable_product->delete( true );
	}

	public function test_trashing_synced_variation_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product();

		foreach ( $variable_product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock( 'online:en:US:gla_' . $variation_id ) );
		}
		$variation_to_delete = wc_get_product( $variable_product->get_children()[0] );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_' . $variation_to_delete->get_id() => $variation_to_delete->get_id() ] ] ) );

		$variation_to_delete->delete();
	}

	public function test_force_deleting_synced_variation_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product();

		foreach ( $variable_product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$this->product_helper->mark_as_synced( $variation, $this->generate_google_product_mock( 'online:en:US:gla_' . $variation_id ) );
		}
		$variation_to_delete = wc_get_product( $variable_product->get_children()[0] );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_' . $variation_to_delete->get_id() => $variation_to_delete->get_id() ] ] ) );

		$variation_to_delete->delete( true );
	}

	public function test_saving_synced_but_not_sync_ready_product_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );

		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		$product->save();
	}

	public function test_trashing_synced_product_wp_post_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();

		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		wp_trash_post( $product->get_id() );
	}

	public function test_force_deleting_synced_product_wp_post_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();

		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		wp_delete_post( $product->get_id(), true );
	}

	public function test_creating_and_updating_post_does_not_schedule_update_job() {
		$this->set_mc_and_notifications();

		$this->update_products_job->expects( $this->never() )
			->method( 'schedule' );

		$post = $this->factory()->post->create_and_get();

		// update post
		$this->factory()->post->update_object( $post->ID, [ 'post_title' => 'Sample title' ] );

		// trash post
		wp_trash_post( $post->ID );

		// un-trash post
		wp_untrash_post( $post->ID );

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_trashing_and_deleting_post_does_not_schedule_delete_job() {
		$this->set_mc_and_notifications();

		$this->delete_products_job->expects( $this->never() )
			->method( 'schedule' );

		$post = $this->factory()->post->create_and_get();

		// trash post
		wp_trash_post( $post->ID );

		// force delete post
		wp_delete_post( $post->ID, true );
	}

	public function test_updating_several_products_schedules_one_update_job() {
		$this->set_mc_and_notifications();

		$products = $this->create_draft_products( 3 );

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ $this->get_ids( $products ) ] ) );

		$this->publish_products( $products );

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_full_batch_is_scheduled_before_the_request_ends() {
		$this->batch_size = 2;
		$this->set_mc_and_notifications();

		$products  = $this->create_draft_products( 3 );
		$scheduled = $this->record_scheduled_update_jobs();

		$this->publish_products( array_slice( $products, 0, 2 ) );
		$this->assertEquals( [ $this->get_ids( array_slice( $products, 0, 2 ) ) ], $scheduled->ids );

		$this->publish_products( array_slice( $products, 2 ) );
		$this->assertCount( 1, $scheduled->ids );

		$this->syncer_hooks->schedule_queued_updates();
		$this->assertEquals( [ $this->get_ids( array_slice( $products, 0, 2 ) ), [ $products[2]->get_id() ] ], $scheduled->ids );
	}

	public function test_product_saved_after_shutdown_is_scheduled_right_away() {
		$this->set_mc_and_notifications();

		$products  = $this->create_draft_products( 1 );
		$scheduled = $this->record_scheduled_update_jobs();

		// Fire shutdown without its callbacks, so nothing would schedule the queue later.
		remove_all_actions( 'shutdown' );
		do_action( 'shutdown' );

		$this->publish_products( $products );

		$this->assertEquals( [ $this->get_ids( $products ) ], $scheduled->ids );
		$this->assertEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $products[0]->get_id() ) ) );
	}

	public function test_product_is_marked_as_pending_only_when_update_job_is_scheduled() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$product->set_status( 'publish' );
		$product->save();

		$this->assertNotEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $product->get_id() ) ) );

		$this->syncer_hooks->schedule_queued_updates();

		$this->assertEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $product->get_id() ) ) );
	}

	public function test_variation_parent_is_marked_as_pending_when_update_job_is_scheduled() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product( null, [ 'status' => 'draft' ] );
		$variable_product->set_status( 'publish' );
		$variable_product->save();

		$this->assertNotEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $variable_product->get_id() ) ) );

		$this->syncer_hooks->schedule_queued_updates();

		$this->assertEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $variable_product->get_id() ) ) );
	}

	public function test_trashing_queued_product_drops_its_update_and_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->update_products_job->expects( $this->never() )
			->method( 'schedule' );
		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		$product->set_status( 'publish' );
		$product->save();
		$product->delete();

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_queued_product_no_longer_sync_ready_is_dropped() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );

		$this->update_products_job->expects( $this->never() )
			->method( 'schedule' );

		$product->set_status( 'publish' );
		$product->save();
		$product->set_status( 'draft' );
		$product->save();

		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_failed_batch_does_not_stop_the_remaining_batches() {
		$this->batch_size = 1;
		$this->set_mc_and_notifications();

		$products = $this->create_draft_products( 2 );

		$this->update_products_job->expects( $this->exactly( 2 ) )
			->method( 'schedule' )
			->willReturnOnConsecutiveCalls(
				$this->throwException( new Exception( 'Unable to schedule' ) ),
				null
			);

		$exceptions_logged = did_action( 'woocommerce_gla_exception' );

		$this->publish_products( $products );

		$this->assertEquals( $exceptions_logged + 1, did_action( 'woocommerce_gla_exception' ) );
		$this->assertNotEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $products[0]->get_id() ) ) );
		$this->assertEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $products[1]->get_id() ) ) );
	}

	public function test_window_queues_products_instead_of_scheduling_update_job() {
		$this->window = 60;
		$this->set_mc_and_notifications();

		$products = $this->create_draft_products( 2 );

		$this->update_products_job->expects( $this->never() )
			->method( 'schedule' );
		$this->queued_updates_job->expects( $this->once() )
			->method( 'queue' )
			->with( $this->get_ids( $products ) );

		$this->publish_products( $products );
		$this->syncer_hooks->schedule_queued_updates();

		$this->assertEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $products[0]->get_id() ) ) );
		$this->assertEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $products[1]->get_id() ) ) );
	}

	public function test_window_queues_one_batch_at_a_time() {
		$this->window     = 60;
		$this->batch_size = 2;
		$this->set_mc_and_notifications();

		$products = $this->create_draft_products( 3 );

		$this->queued_updates_job->expects( $this->exactly( 2 ) )
			->method( 'queue' )
			->withConsecutive(
				[ $this->get_ids( array_slice( $products, 0, 2 ) ) ],
				[ [ $products[2]->get_id() ] ]
			);

		$this->publish_products( $products );
		$this->syncer_hooks->schedule_queued_updates();
	}

	public function test_window_failed_queue_does_not_mark_products_as_pending() {
		$this->window = 60;
		$this->set_mc_and_notifications();

		$products = $this->create_draft_products( 1 );

		$this->queued_updates_job->method( 'queue' )
			->willThrowException( new Exception( 'Unable to queue' ) );

		$exceptions_logged = did_action( 'woocommerce_gla_exception' );

		$this->publish_products( $products );
		$this->syncer_hooks->schedule_queued_updates();

		$this->assertEquals( $exceptions_logged + 1, did_action( 'woocommerce_gla_exception' ) );
		$this->assertNotEquals( SyncStatus::PENDING, $this->product_helper->get_sync_status( wc_get_product( $products[0]->get_id() ) ) );
	}

	public function test_trashing_product_removes_it_from_the_delayed_queue() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();

		$this->queued_updates_job->expects( $this->once() )
			->method( 'dequeue' )
			->with( $product->get_id() );

		wp_trash_post( $product->get_id() );
	}

	public function test_duplicate_product_excludes_queued_update_meta() {
		$this->set_mc_and_notifications();

		$this->assertContains( '_wc_gla_sync_queued_at', $this->syncer_hooks->duplicate_product_exclude_meta( [] ) );
	}

	public function test_actions_not_defined_when_mc_not_ready() {
		$this->set_mc_and_notifications( false );

		$this->assertFalse( has_action( 'woocommerce_new_product', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertFalse( has_action( 'woocommerce_new_product_variation', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertFalse( has_action( 'woocommerce_update_product', [ $this->syncer_hooks, 'update_by_object' ] ) );
		$this->assertFalse( has_action( 'woocommerce_update_product_variation', [ $this->syncer_hooks, 'update_by_object' ] ) );
		$this->assertFalse( has_action( 'woocommerce_process_product_meta', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertFalse( has_action( 'wp_trash_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertFalse( has_action( 'before_delete_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertFalse( has_action( 'woocommerce_before_delete_product_variation', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertFalse( has_action( 'trashed_post', [ $this->syncer_hooks, 'delete' ] ) );
		$this->assertFalse( has_action( 'deleted_post', [ $this->syncer_hooks, 'delete' ] ) );
		$this->assertFalse( has_action( 'untrashed_post', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertFalse( has_action( 'shutdown', [ $this->syncer_hooks, 'schedule_queued_updates' ] ) );
		$this->assertFalse( has_filter( 'woocommerce_duplicate_product_exclude_meta', [ $this->syncer_hooks, 'duplicate_product_exclude_meta' ] ) );
	}

	public function test_actions_defined_when_mc_ready() {
		$this->set_mc_and_notifications();

		$this->assertEquals( 90, has_action( 'woocommerce_new_product', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_new_product_variation', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_update_product', [ $this->syncer_hooks, 'update_by_object' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_update_product_variation', [ $this->syncer_hooks, 'update_by_object' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_process_product_meta', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'wp_trash_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertEquals( 90, has_action( 'before_delete_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_before_delete_product_variation', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertEquals( 90, has_action( 'trashed_post', [ $this->syncer_hooks, 'delete' ] ) );
		$this->assertEquals( 90, has_action( 'deleted_post', [ $this->syncer_hooks, 'delete' ] ) );
		$this->assertEquals( 90, has_action( 'untrashed_post', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertEquals( 1, has_action( 'shutdown', [ $this->syncer_hooks, 'schedule_queued_updates' ] ) );
		$this->assertEquals( 90, has_filter( 'woocommerce_duplicate_product_exclude_meta', [ $this->syncer_hooks, 'duplicate_product_exclude_meta' ] ) );
	}

	/**
	 * Set the SyncerHooks class with specific features.
	 *
	 * @param bool $mc_status True if MC is ready. { @see MerchantCenterService::is_ready_for_syncing() }
	 */
	public function set_mc_and_notifications( bool $mc_status = true ) {
		$this->merchant_center->expects( $this->any() )
			->method( 'is_ready_for_syncing' )
			->willReturn( $mc_status );

		$this->syncer_hooks = new SyncerHooks( $this->batch_helper, $this->product_helper, $this->job_repository, $this->merchant_center, $this->wc );
		$this->syncer_hooks->register();
	}

	/**
	 * @param int $count
	 *
	 * @return WC_Product[]
	 */
	protected function create_draft_products( int $count ): array {
		$products = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$products[] = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		}

		return $products;
	}

	/**
	 * @param WC_Product[] $products
	 */
	protected function publish_products( array $products ): void {
		foreach ( $products as $product ) {
			$product->set_status( 'publish' );
			$product->save();
		}
	}

	/**
	 * @param WC_Product[] $products
	 *
	 * @return int[]
	 */
	protected function get_ids( array $products ): array {
		return array_map(
			function ( WC_Product $product ) {
				return $product->get_id();
			},
			$products
		);
	}

	/**
	 * Record the product IDs of every update job scheduled from now on.
	 *
	 * @return object An object whose `ids` property lists the product IDs of each scheduled job.
	 */
	protected function record_scheduled_update_jobs(): object {
		$scheduled      = new stdClass();
		$scheduled->ids = [];

		$this->update_products_job->method( 'schedule' )
			->willReturnCallback(
				function ( array $args ) use ( $scheduled ) {
					$scheduled->ids[] = $args[0];
				}
			);

		return $scheduled;
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->merchant_center     = $this->createMock( MerchantCenterService::class );
		$this->update_products_job = $this->createMock( UpdateProducts::class );
		$this->delete_products_job = $this->createMock( DeleteProducts::class );
		$this->job_repository      = $this->createMock( JobRepository::class );
		$this->queued_updates_job  = $this->createMock( ProcessQueuedProductUpdates::class );

		$this->update_products_job->method( 'get_batch_size' )
			->willReturnCallback(
				function () {
					return $this->batch_size;
				}
			);

		$this->queued_updates_job->method( 'get_batch_window' )
			->willReturnCallback(
				function () {
					return $this->window;
				}
			);

		$this->job_repository->expects( $this->any() )
			->method( 'get' )
			->willReturnMap(
				[
					[ UpdateProducts::class, $this->update_products_job ],
					[ DeleteProducts::class, $this->delete_products_job ],
					[ ProcessQueuedProductUpdates::class, $this->queued_updates_job ],
				]
			);

		$this->batch_helper   = $this->container->get( BatchProductHelper::class );
		$this->product_helper = $this->container->get( ProductHelper::class );
		$this->wc             = $this->container->get( WC::class );
	}
}
