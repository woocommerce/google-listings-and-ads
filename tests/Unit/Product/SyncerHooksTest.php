<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\ProductNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\SyncerHooks;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

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

	/**
	 * @var MockObject|NotificationsService
	 */
	protected $notification_service;

	/**
	 * @var MockObject|ProductNotificationJob
	 */
	protected $product_notification_job;

	/** @var WC $wc */
	protected $wc;

	/** @var SyncerHooks $syncer_hooks */
	protected $syncer_hooks;

	public function test_create_new_simple_product_schedules_update_job() {
		$this->set_mc_and_notifications();

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' );

		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		WC_Helper_Product::create_simple_product( true, [ 'status' => 'publish' ] );
	}

	public function test_update_simple_product_schedules_update_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $product->get_id() ] ] ) );

		$product->set_status( 'publish' );
		$product->save();
	}

	public function test_multiple_update_events_for_same_product_only_schedules_update_job_once() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $product->get_id() ] ] ) );

		// update #1
		$product->set_status( 'publish' );
		$product->save();

		// update #2
		$product->set_description( 'Sample description' );
		$product->save();
	}

	public function test_create_variable_product_schedules_update_job_for_all_variations() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product( null, [ 'status' => 'draft' ] );
		$variation_ids    = $variable_product->get_children();
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ $variation_ids ] ) );

		$variable_product->set_status( 'publish' );
		$variable_product->save();
	}

	public function test_adding_variation_schedules_update_job() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product();
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

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
	}

	public function test_trashing_product_does_not_schedules_delete_job_if_product_is_not_synced() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->delete_products_job->expects( $this->never() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $product->get_id() ] ] ) );

		$product->delete();
	}

	public function test_trashing_synced_product_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		$product->delete();
	}

	public function test_force_deleting_synced_product_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();
		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		$product->delete( true );
	}

	public function test_trashing_synced_variable_schedules_delete_job_for_all_variations() {
		$this->set_mc_and_notifications();

		$variable_product = $this->create_variation_product();
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

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
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

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
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

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
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

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
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		$product->save();
	}

	public function test_trashing_synced_product_wp_post_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$this->product_helper->mark_as_synced( $product, $this->generate_google_product_mock( 'online:en:US:gla_1' ) );

		$this->delete_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ 'online:en:US:gla_1' => $product->get_id() ] ] ) );

		wp_trash_post( $product->get_id() );
	}

	public function test_force_deleting_synced_product_wp_post_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$product = WC_Helper_Product::create_simple_product();
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

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
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$post = $this->factory()->post->create_and_get();

		// update post
		$this->factory()->post->update_object( $post->ID, [ 'post_title' => 'Sample title' ] );

		// trash post
		wp_trash_post( $post->ID );

		// un-trash post
		wp_untrash_post( $post->ID );
	}

	public function test_trashing_and_deleting_post_does_not_schedule_delete_job() {
		$this->set_mc_and_notifications();

		$this->delete_products_job->expects( $this->never() )
			->method( 'schedule' );
		$this->product_notification_job->expects( $this->never() )->method( 'schedule' );

		$post = $this->factory()->post->create_and_get();

		// trash post
		wp_trash_post( $post->ID );

		// force delete post
		wp_delete_post( $post->ID, true );
	}

	public function test_create_product_triggers_notification_created() {
		$this->set_mc_and_notifications( true, true );

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->product_notification_job->expects( $this->once() )
			->method( 'schedule' )->with(
				$this->equalTo(
					[
						'item_id' => $product->get_id(),
						'topic'   => NotificationsService::TOPIC_PRODUCT_CREATED,
					]
				)
			);

		$this->update_products_job->expects( $this->never() )
			->method( 'schedule' );

		$product->set_status( 'publish' );
		$product->save();
	}

	public function test_create_product_triggers_notification_updated() {
		$this->set_mc_and_notifications( true, true );

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->update_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->product_notification_job->expects( $this->once() )
			->method( 'schedule' )->with(
				$this->equalTo(
					[
						'item_id' => $product->get_id(),
						'topic'   => NotificationsService::TOPIC_PRODUCT_UPDATED,
					]
				)
			);
		$product->set_status( 'publish' );
		$this->product_helper->set_notification_status( $product, NotificationStatus::NOTIFICATION_CREATED );
		$product->save();
	}

	public function test_create_product_triggers_notification_delete() {
		$this->set_mc_and_notifications( true, true );

		$product = WC_Helper_Product::create_simple_product( true, [ 'status' => 'draft' ] );
		$this->delete_products_job->expects( $this->never() )
			->method( 'schedule' );

		$this->product_notification_job->expects( $this->once() )
			->method( 'schedule' )->with(
				$this->equalTo(
					[
						'item_id' => $product->get_id(),
						'topic'   => NotificationsService::TOPIC_PRODUCT_DELETED,
					]
				)
			);
		$product->set_status( 'publish' );
		$product->add_meta_data( '_wc_gla_visibility', ChannelVisibility::DONT_SYNC_AND_SHOW, true );
		$this->product_helper->set_notification_status( $product, NotificationStatus::NOTIFICATION_CREATED );
		$product->save();
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
		$this->assertEquals( 90, has_filter( 'woocommerce_duplicate_product_exclude_meta', [ $this->syncer_hooks, 'duplicate_product_exclude_meta' ] ) );
	}


	/**
	 * Set the SyncerHooks class with specific features.
	 *
	 * @param bool $mc_status True if MC is ready. { @see MerchantCenterService::is_ready_for_syncing() }
	 * @param bool $notifications_status True if NotificationsService is enabled. { @see NotificationsService::is_enabled() }
	 */
	public function set_mc_and_notifications( bool $mc_status = true, bool $notifications_status = false ) {
		$this->merchant_center->expects( $this->any() )
			->method( 'is_ready_for_syncing' )
			->willReturn( $mc_status );

		$this->notification_service->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( $notifications_status );

		$this->syncer_hooks = new SyncerHooks( $this->batch_helper, $this->product_helper, $this->job_repository, $this->merchant_center, $this->notification_service, $this->wc );
		$this->syncer_hooks->register();
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->merchant_center          = $this->createMock( MerchantCenterService::class );
		$this->update_products_job      = $this->createMock( UpdateProducts::class );
		$this->delete_products_job      = $this->createMock( DeleteProducts::class );
		$this->product_notification_job = $this->createMock( ProductNotificationJob::class );
		$this->job_repository           = $this->createMock( JobRepository::class );
		$this->notification_service     = $this->createMock( NotificationsService::class );

		$this->job_repository->expects( $this->any() )
			->method( 'get' )
			->willReturnMap(
				[
					[ UpdateProducts::class, $this->update_products_job ],
					[ DeleteProducts::class, $this->delete_products_job ],
					[ ProductNotificationJob::class, $this->product_notification_job ],
				]
			);

		$this->batch_helper   = $this->container->get( BatchProductHelper::class );
		$this->product_helper = $this->container->get( ProductHelper::class );
		$this->wc             = $this->container->get( WC::class );
	}
}
