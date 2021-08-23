<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Coupon;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncerHooks
 *
 * Hooks to various WooCommerce and WordPress actions to provide automatic coupon sync functionality.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class SyncerHooks implements Service, Registerable {
    
    use PluginHelper;
     
    /**
     * @var CouponHelper
     */
    protected $coupon_helper;
    
    /**
     * @var UpdateCoupon
     */
    protected $update_coupon_job;
    
    /**
     * @var DeleteCoupon
     */
    protected $delete_coupon_job;
    
    /**
     * @var MerchantCenterService
     */
    protected $merchant_center;
    
    /**
     * @var WC
     */
    protected $wc;
    
    /**
     * SyncerHooks constructor.
     *
     * @param JobRepository         $job_repository
     * @param MerchantCenterService $merchant_center
     * @param WC                    $wc
     */
    public function __construct(
        CouponHelper $coupon_helper,
        JobRepository $job_repository,
        MerchantCenterService $merchant_center,
        WC $wc
        ) {
            $this->update_coupon_job  = $job_repository->get( UpdateCoupon::class );
            $this->delete_coupon_job  = $job_repository->get( DeleteCoupon::class );
            $this->coupon_helper      = $coupon_helper;
            $this->merchant_center    = $merchant_center;
            $this->wc                 = $wc;
    }
    
    /**
     * Register a service.
     */
    public function register(): void {
        // only register the hooks if Merchant Center is set up and connected.
        if ( ! $this->merchant_center->is_connected() ) {
            return;
        }
        
        $update_by_object = function ( int $coupon_id, WC_Coupon $coupon) {
            $this->handle_update_coupon( $coupon );
        };
        
        $update_by_id = function ( int $coupon_id ) {
            $coupon = $this->wc->maybe_get_coupon( $coupon_id );
            if ( $coupon instanceof WC_Coupon) {
                $this->update_coupon_job->schedule( [ $coupon_id ] );
            }
        };
        
        $delete_by_id = function ( int $coupon_id ) {
            $this->delete_coupon_job->schedule( [ $coupon_id ] );
        };
        
        // when a coupon is added / updated, schedule a update job.
        add_action( 'woocommerce_new_coupon', $update_by_object, 90, 2 );
        add_action( 'woocommerce_update_coupon', $update_by_object, 90, 2 );
        // when a product is trashed or removed, schedule a delete job.
        add_action( 'woocommerce_delete_coupon', $delete_by_id, 90, 2);
        add_action( 'woocommerce_trash_coupon', $delete_by_id, 90, 2);
        
        // when a coupon is restored from trash, schedule a update job.
        add_action( 'untrashed_post', $update_by_id, 90 );
    }
    
    /**
     * Handle updating of a coupon.
     *
     * @param WC_Coupon $coupon The coupon being saved.
     *
     * @return void
     */
    protected function handle_update_coupon( WC_Coupon $coupon ) {
        $coupon_id = $coupon->get_id();
        
        // Schedule an update job if product sync is enabled.
        if ( $this->coupon_helper->is_sync_ready( $coupon ) ) {
            $this->coupon_helper->mark_as_pending( $coupon );
            $this->update_coupon_job->schedule( [ $coupon_id ] );
        } elseif ( $this->coupon_helper->is_coupon_synced( $coupon ) ) {
            // Delete the coupon from Google Merchant Center if it's already synced BUT it is not sync ready after the edit.
            $this->delete_coupon_job->schedule( [ $coupon_id ] );
            
            do_action(
                'woocommerce_gla_debug_message',
                sprintf( 'Deleting coupon (ID: %s) from Google Merchant Center because it is not ready to be synced.', $coupon->get_id() ),
                __METHOD__
            );
        } else {
            $this->coupon_helper->mark_as_unsynced( $coupon );
        }
    }
}

