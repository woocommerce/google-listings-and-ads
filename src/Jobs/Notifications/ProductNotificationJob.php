<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\HelperNotificationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductNotificationJob
 * Class for the Product Notifications Jobs
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ProductNotificationJob extends AbstractItemNotificationJob {

	/**
	 * @var ProductHelper $helper
	 */
	protected $helper;

	/**
	 * Notifications Jobs constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param NotificationsService      $notifications_service
	 * @param ProductHelper             $helper
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		NotificationsService $notifications_service,
		HelperNotificationInterface $helper
	) {
		$this->notifications_service = $notifications_service;
		$this->helper                = $helper;
		parent::__construct( $action_scheduler, $monitor, $notifications_service );
	}

	/**
	 * Get the product
	 *
	 * @param int $item_id
	 * @return \WC_Product
	 */
	protected function get_item( int $item_id ) {
		return $this->helper->get_wc_product( $item_id );
	}

	/**
	 * Get the Product Helper
	 *
	 * @return ProductHelper
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
		return 'products';
	}
}
