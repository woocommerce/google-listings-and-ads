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
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;

/**
 * Class SyncerHooksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 *         
 * @property MockObject|MerchantCenterService $merchant_center
 * @property MockObject|JobRepository $job_repository
 * @property MockObject|UpdateCoupon $update_coupon_job
 * @property WC $wc
 * @property SyncerHooks $syncer_hooks
 */
class SyncerHooksTest extends ContainerAwareUnitTest {
    use CouponTrait;

    public function test_create_new_simple_coupon_schedules_update_job() {
        $this->update_coupon_job->expects( $this->once() )
            ->method( 'schedule' );
        $string_code = 'test_coupon_codes';
        $coupon = new WC_Coupon();
        $this->coupon_helper->mark_as_synced( $coupon, 'fake_google_id', 'US' );
        $coupon->set_code( $string_code );
        $coupon->save();
    }

    public function test_update_simple_coupon_schedules_update_job() {
        $string_code = 'test_coupon_codes';
        $coupon = new WC_Coupon();
        $this->coupon_helper->mark_as_synced( $coupon, 'fake_google_id', 'US' );
        $coupon->set_code( $string_code );
        $coupon->save();

        $this->update_coupon_job->expects( $this->once() )
            ->method( 'schedule' )
            ->with( $this->equalTo( [[$coupon->get_id()]] ) );
        $coupon->add_meta_data( 'test_coupon_field', 'testing', true );
        $coupon->save();
    }

    public function test_update_invisible_coupon_does_not_schedule_update_job() {
        $string_code = 'test_coupon_codes';
        $coupon = new WC_Coupon();
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
        $coupon = $this->create_ready_to_delete_coupon();

        $adapted_coupon = new WCCouponAdapter( [ 'wc_coupon' => $coupon ] );
        $adapted_coupon->disable_promotion( $coupon );
        $expected_coupon_entry = new DeleteCouponEntry( 
            $coupon->get_id(),
            $adapted_coupon,
            ['US' => 'google_id'] );
        $this->delete_coupon_job->expects( $this->once() )
            ->method( 'schedule' )
            ->with( $this->callback( function ( $entries ) use ( $expected_coupon_entry ) {
                return $entries[0]->get_wc_coupon_id() === $expected_coupon_entry->get_wc_coupon_id();
            } ) );

        wp_trash_post( $coupon->get_id() );
    }

    public function test_delete_simple_coupon_schedules_delete_job() {
        $coupon = $this->create_ready_to_delete_coupon();

        $adapted_coupon = new WCCouponAdapter( [ 'wc_coupon' => $coupon ] );
        $adapted_coupon->disable_promotion( $coupon );
        $expected_coupon_entry = new DeleteCouponEntry( 
            $coupon->get_id(),
            $adapted_coupon,
            ['US' => 'google_id'] );
        $this->delete_coupon_job->expects( $this->once() )
            ->method( 'schedule' )
            ->with( $this->callback( function ( $entries ) use ( $expected_coupon_entry ) {
                return $entries[0]->get_wc_coupon_id() === $expected_coupon_entry->get_wc_coupon_id();
            } ) );

        // force delete post
        wp_delete_post( $coupon->get_id(), true );
    }

    public function test_untrash_simple_coupon_schedules_update_job() {
        $coupon = $this->create_ready_to_delete_coupon();
        $coupon_id = $coupon->get_id();
        $coupon->delete();

        $this->update_coupon_job->expects( $this->once() )
            ->method( 'schedule' )
            ->with( $this->equalTo( [[$coupon_id]] ) );
        // untrash coupon
        wp_untrash_post( $coupon_id );
    }

    public function test_modify_post_does_not_schedule_update_job() {
        $this->update_coupon_job->expects( $this->never() )
            ->method( 'schedule' );

        $post = $this->factory()->post->create_and_get();
        // update post
        $this->factory()->post->update_object( 
            $post->ID,
            ['post_title' => 'Sample title'] );
        // trash post
        wp_trash_post( $post->ID );
        // un-trash post
        wp_untrash_post( $post->ID );
    }

    /**
     * Runs before each test is executed.
     */
    public function setUp() {
        parent::setUp();

        $this->login_as_administrator();

        $this->merchant_center = $this->createMock( 
            MerchantCenterService::class );
        $this->merchant_center->expects( $this->any() )
            ->method( 'is_connected' )
            ->willReturn( true );

        $this->update_coupon_job = $this->createMock( UpdateCoupon::class );
        $this->delete_coupon_job = $this->createMock( DeleteCoupon::class );
        $this->job_repository = $this->createMock( JobRepository::class );
        $this->job_repository->expects( $this->any() )
            ->method( 'get' )
            ->willReturnMap( 
            [
                [DeleteCoupon::class,$this->delete_coupon_job],
                [UpdateCoupon::class,$this->update_coupon_job]] );

        $this->wc = $this->container->get( WC::class );
        $this->coupon_helper = $this->container->get( CouponHelper::class );
        $this->syncer_hooks = new SyncerHooks( 
            $this->coupon_helper,
            $this->job_repository,
            $this->merchant_center,
            $this->wc );

        $this->syncer_hooks->register();
    }
}
