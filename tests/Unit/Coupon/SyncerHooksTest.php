<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\SyncerHooks;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\WCCouponAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\DeleteCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;
use WC_Helper_Coupon;
use WC_Helper_Product;

/**
 * Class SyncerHooksTest
 *
 * @group SyncerHooks
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon
 */
class SyncerHooksTest extends ContainerAwareUnitTest {

	use CouponTrait;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|DeleteCoupon $delete_coupon_job */
	protected $delete_coupon_job;

	/** @var MockObject|UpdateCoupon $update_coupon_job */
	protected $update_coupon_job;

	/** @var CouponHelper $coupon_helper */
	protected $coupon_helper;

	/** @var WC $wc */
	protected $wc;

	/** @var WP $wp */
	protected $wp;

	/** @var SyncerHooks $syncer_hooks */
	protected $syncer_hooks;

	public function test_create_new_simple_coupon_schedules_update_job() {
		$this->set_mc_and_notifications();
		$this->update_coupon_job->expects( $this->once() )
			->method( 'schedule' );
		$string_code = 'test_coupon_codes';
		$coupon      = new WC_Coupon();
		$this->coupon_helper->mark_as_synced( $coupon, 'fake_google_id', 'US' );
		$coupon->set_code( $string_code );
		$coupon->save();
	}

	public function test_update_simple_coupon_schedules_update_job() {
		$this->set_mc_and_notifications();
		$string_code = 'test_coupon_codes';
		$coupon      = new WC_Coupon();
		$this->coupon_helper->mark_as_synced( $coupon, 'fake_google_id', 'US' );
		$coupon->set_code( $string_code );
		$coupon->save();

		$this->update_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $coupon->get_id() ] ] ) );
		$coupon->add_meta_data( 'test_coupon_field', 'testing', true );
		$coupon->save();
	}

	public function test_update_invisible_coupon_does_not_schedule_update_job() {
		$this->set_mc_and_notifications();
		$string_code = 'test_coupon_codes';
		$coupon      = new WC_Coupon();
		$coupon->update_meta_data( '_wc_gla_visibility', 'dont-sync-and-show' );
		$coupon->save_meta_data();
		$coupon->set_code( $string_code );
		$coupon->save();

		$this->update_coupon_job->expects( $this->never() )
			->method( 'schedule' );
		$coupon->add_meta_data( 'test_coupon_field', 'testing', true );
		$coupon->save();
	}

	public function test_trash_simple_coupon_schedules_delete_job() {
		$this->set_mc_and_notifications();
		$coupon = $this->create_ready_to_delete_coupon();

		$adapted_coupon = new WCCouponAdapter( [ 'wc_coupon' => $coupon ] );
		$adapted_coupon->disable_promotion( $coupon );
		$expected_coupon_entry = new DeleteCouponEntry(
			$coupon->get_id(),
			$adapted_coupon->get_promotion(),
			[ 'US' => 'google_id' ]
		);
		$this->delete_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				$this->callback(
					function ( $entries ) use ( $expected_coupon_entry ) {
						return $entries[0]->get_wc_coupon_id() === $expected_coupon_entry->get_wc_coupon_id();
					}
				)
			);

		wp_trash_post( $coupon->get_id() );
	}

	public function test_delete_simple_coupon_schedules_delete_job() {
		$this->set_mc_and_notifications();

		$coupon         = $this->create_ready_to_delete_coupon();
		$adapted_coupon = new WCCouponAdapter( [ 'wc_coupon' => $coupon ] );
		$adapted_coupon->disable_promotion( $coupon );
		$expected_coupon_entry = new DeleteCouponEntry(
			$coupon->get_id(),
			$adapted_coupon->get_promotion(),
			[ 'US' => 'google_id' ]
		);
		$this->delete_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				$this->callback(
					function ( $entries ) use ( $expected_coupon_entry ) {
						return $entries[0]->get_wc_coupon_id() === $expected_coupon_entry->get_wc_coupon_id();
					}
				)
			);

		// force delete post
		wp_delete_post( $coupon->get_id(), true );
	}

	public function test_untrash_simple_coupon_schedules_update_job() {
		$this->set_mc_and_notifications();

		$coupon    = $this->create_ready_to_delete_coupon();
		$coupon_id = $coupon->get_id();
		$coupon->delete();

		$this->update_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $coupon_id ] ] ) );

		// untrash coupon
		wp_untrash_post( $coupon_id );
	}

	public function test_modify_post_does_not_schedule_update_job() {
		$this->set_mc_and_notifications();

		$this->update_coupon_job->expects( $this->never() )
			->method( 'schedule' );

		$post = $this->factory()->post->create_and_get();
		// update post
		$this->factory()->post->update_object(
			$post->ID,
			[ 'post_title' => 'Sample title' ]
		);
		// trash post
		wp_trash_post( $post->ID );
		// un-trash post
		wp_untrash_post( $post->ID );
	}

	public function test_product_brands_updated_schedules_update_job() {
		// compatibility-code "WC < 9.4" -- Brands in core was added in WooCommerce 9.4
		if ( version_compare( WC_VERSION, '9.4', '<' ) ) {
			self::markTestSkipped( 'WooCommerce 9.4 or newer is needed to test WooCommerce Brands in core.' );
		}

		require_once WC_ABSPATH . '/includes/class-wc-brands.php';
		\WC_Brands::init_taxonomy();

		// Create products and brands.
		/**
		 * @var WC_Product $product_1
		 */
		$product_1 = WC_Helper_Product::create_simple_product();
		$product_2 = WC_Helper_Product::create_simple_product();
		$brand_1   = wp_insert_term( 'Brand 1', 'product_brand' );

		$brand_taxonomy = 'product_brand';

		// Set the brand 1 for the product 1 and 2.
		wp_set_post_terms( $product_1->get_id(), $brand_1['term_id'], $brand_taxonomy );
		wp_set_post_terms( $product_2->get_id(), $brand_1['term_id'], $brand_taxonomy );

		// Create a coupon.
		/**
		 * @var WC_Coupon $coupon
		 */
		$coupon = WC_Helper_Coupon::create_coupon( uniqid() );
		$coupon->set_status( 'publish' );
		$coupon->add_meta_data( '_wc_gla_visibility', ChannelVisibility::SYNC_AND_SHOW, true );
		// Add brand 1 to coupon inclusion restriction.
		$coupon->add_meta_data( 'product_brands', [ (int) $brand_1['term_id'] ], true );
		$coupon->save();

		$this->set_mc_and_notifications();
		$this->update_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $coupon->get_id() ] ] ) );

		// Remove brand 1 from the product 1.
		wp_set_object_terms( $product_1->get_id(), [], $brand_taxonomy );
	}

	public function test_actions_not_defined_when_mc_not_ready() {
		$this->set_mc_and_notifications( false );

		$this->assertFalse( has_action( 'woocommerce_new_coupon', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertFalse( has_action( 'woocommerce_update_coupon', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertFalse( has_action( 'woocommerce_gla_bulk_update_coupon', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertFalse( has_action( 'wp_trash_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertFalse( has_action( 'before_delete_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertFalse( has_action( 'trashed_post', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertFalse( has_action( 'deleted_post', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertFalse( has_action( 'woocommerce_delete_coupon', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertFalse( has_action( 'woocommerce_trash_coupon', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertfalse( has_action( 'set_object_terms', [ $this->syncer_hooks, 'maybe_update_by_id_when_terms_updated' ] ) );
	}

	public function test_actions_defined_when_mc_ready() {
		$this->set_mc_and_notifications();

		$this->assertEquals( 90, has_action( 'woocommerce_new_coupon', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_update_coupon', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_gla_bulk_update_coupon', [ $this->syncer_hooks, 'update_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'wp_trash_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertEquals( 90, has_action( 'before_delete_post', [ $this->syncer_hooks, 'pre_delete' ] ) );
		$this->assertEquals( 90, has_action( 'trashed_post', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'deleted_post', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_delete_coupon', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'woocommerce_trash_coupon', [ $this->syncer_hooks, 'delete_by_id' ] ) );
		$this->assertEquals( 90, has_action( 'set_object_terms', [ $this->syncer_hooks, 'maybe_update_by_id_when_terms_updated' ] ) );
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

		$this->syncer_hooks = new SyncerHooks(
			$this->coupon_helper,
			$this->job_repository,
			$this->merchant_center,
			$this->wc,
			$this->wp
		);

		$this->syncer_hooks->register();
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->merchant_center = $this->createMock(
			MerchantCenterService::class
		);

		$this->update_coupon_job = $this->createMock( UpdateCoupon::class );
		$this->delete_coupon_job = $this->createMock( DeleteCoupon::class );

		$this->job_repository = $this->createMock( JobRepository::class );
		$this->job_repository->expects( $this->any() )
			->method( 'get' )
			->willReturnMap(
				[
					[ DeleteCoupon::class, $this->delete_coupon_job ],
					[ UpdateCoupon::class, $this->update_coupon_job ],
				]
			);

		$this->wc            = $this->container->get( WC::class );
		$this->wp            = $this->container->get( WP::class );
		$this->coupon_helper = $this->container->get( CouponHelper::class );
	}
}
