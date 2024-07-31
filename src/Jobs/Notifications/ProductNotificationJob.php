<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductNotificationJob
 * Class for the Product Notifications Jobs
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class ProductNotificationJob extends AbstractItemNotificationJob {

	use PluginHelper;

	/**
	 * @var ProductHelper $helper
	 */
	protected $helper;

	/**
	 * Notifications Jobs constructor.
	 *
	 * @param ActionSchedulerInterface    $action_scheduler
	 * @param ActionSchedulerJobMonitor   $monitor
	 * @param NotificationsService        $notifications_service
	 * @param HelperNotificationInterface $helper
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		NotificationsService $notifications_service,
		HelperNotificationInterface $helper
	) {
		$this->helper = $helper;
		parent::__construct( $action_scheduler, $monitor, $notifications_service );
	}

	/**
	 * Override Product Notification adding Offer ID for deletions.
	 * The offer_id might match the real offer ID or not, depending on whether the product has been synced by us or not.
	 * Google should check on their side if the product actually exists.
	 *
	 * @param array $args Arguments with the item id and the topic.
	 */
	protected function process_items( $args ): void {
		if ( isset( $args['topic'] ) && isset( $args['item_id'] ) && $this->is_delete_topic( $args['topic'] ) ) {
			$args['data'] = [ 'offer_id' => $this->helper->get_offer_id( $args['item_id'] ) ];
		}

		parent::process_items( $args );
	}

	/**
	 * Get the product
	 *
	 * @param int $item_id
	 * @throws InvalidValue If the given ID doesn't reference a valid product.
	 *
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
