<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractCouponSyncerJob
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractCouponSyncerJob extends AbstractActionSchedulerJob implements ProductSyncerJobInterface {

	/**
     * @var WC
     */
    protected $wc;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * SyncProducts constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param CouponSyncer             $coupon_syncer
	 * @param CouponRepository         $coupon_repository
	 * @param MerchantCenterService     $merchant_center
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		//CouponSyncer $coupon_syncer,
	    WC $wc,
		MerchantCenterService $merchant_center
	) {
	    $this->wc  = $wc;
		$this->merchant_center    = $merchant_center;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Get whether Merchant Center is connected.
	 *
	 * @return bool
	 */
	public function is_mc_connected(): bool {
		return $this->merchant_center->is_connected();
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return ! $this->is_running( $args ) && $this->is_mc_connected();
	}
}
