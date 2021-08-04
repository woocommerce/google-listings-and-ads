<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

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
    
    protected const SCHEDULE_TYPE_UPDATE = 'update';
    protected const SCHEDULE_TYPE_DELETE = 'delete';
    
    /**
     * @var CouponHelper
     */
    //protected $coupon_helper;
    
    /**
     * @var UpdateCoupon
     */
    protected $update_coupon_job;
    
    
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
        JobRepository $job_repository,
        MerchantCenterService $merchant_center,
        WC $wc
        ) {
            $this->update_coupon_job  = $job_repository->get( UpdateCoupon::class );
            $this->merchant_center     = $merchant_center;
            $this->wc                  = $wc;
    }
    
    /**
     * Register a service.
     */
    public function register(): void {
        // only register the hooks if Merchant Center is set up and connected.
        if ( ! $this->merchant_center->is_connected() ) {
            return;
        }
        
        $update_by_id = function ( int $coupon_id ) {
            $this->update_coupon_job->schedule($coupon_id);
        };
        
        // when a coupon is added / updated, schedule a update job.
        add_action( 'woocommerce_new_coupon', $update_by_id, 90, 2 );
        add_action( 'woocommerce_update_coupon', $update_by_id, 90, 2 );
    }
}

