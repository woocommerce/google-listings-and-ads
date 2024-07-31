<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\HelperNotificationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponNotificationJob
 * Class for the Coupons Notifications Jobs
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class CouponNotificationJob extends AbstractItemNotificationJob {

	/**
	 * @var CouponHelper $helper
	 */
	protected $helper;

	/**
	 * Notifications Jobs constructor.
	 *
	 * @param ActionSchedulerInterface    $action_scheduler
	 * @param ActionSchedulerJobMonitor   $monitor
	 * @param NotificationsService        $notifications_service
	 * @param HelperNotificationInterface $coupon_helper
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		NotificationsService $notifications_service,
		HelperNotificationInterface $coupon_helper
	) {
		$this->helper = $coupon_helper;
		parent::__construct( $action_scheduler, $monitor, $notifications_service );
	}

	/**
	 * Get the coupon
	 *
	 * @param int $item_id
	 * @return \WC_Coupon
	 */
	protected function get_item( int $item_id ) {
		return $this->helper->get_wc_coupon( $item_id );
	}

	/**
	 * Get the Coupon Helper
	 *
	 * @return HelperNotificationInterface
	 */
	public function get_helper(): HelperNotificationInterface {
		return $this->helper;
	}

	/**
	 * Get the job name
	 *
	 * @return string
	 */
	public function get_job_name(): string {
		return 'coupons';
	}
}
