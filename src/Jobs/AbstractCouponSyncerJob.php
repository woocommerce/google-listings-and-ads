<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractCouponSyncerJob
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
abstract class AbstractCouponSyncerJob extends AbstractActionSchedulerJob {

	/**
	 * @var CouponHelper
	 */
	protected $coupon_helper;

	/**
	 * @var CouponSyncer
	 */
	protected $coupon_syncer;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * AbstractCouponSyncerJob constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param CouponHelper              $coupon_helper
	 * @param CouponSyncer              $coupon_syncer
	 * @param WC                        $wc
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		CouponHelper $coupon_helper,
		CouponSyncer $coupon_syncer,
		WC $wc
	) {
		$this->coupon_helper = $coupon_helper;
		$this->coupon_syncer = $coupon_syncer;
		$this->wc            = $wc;
		parent::__construct( $action_scheduler, $monitor );
	}

	/**
	 * Can the job be scheduled.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return ! $this->is_running( $args );
	}
}
