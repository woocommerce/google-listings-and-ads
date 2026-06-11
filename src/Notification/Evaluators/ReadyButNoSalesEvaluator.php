<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PolicyComplianceCheck;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReadyButNoSalesEvaluator
 *
 * Fires when payment gateways are active, shipping zones are configured, and the order count is zero.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class ReadyButNoSalesEvaluator implements NotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait;

	/** @var PolicyComplianceCheck */
	protected $policy_compliance_check;

	/** @var WC */
	protected $wc;

	/**
	 * ReadyButNoSalesEvaluator constructor.
	 *
	 * @param PolicyComplianceCheck $policy_compliance_check
	 * @param WC                    $wc
	 */
	public function __construct( PolicyComplianceCheck $policy_compliance_check, WC $wc ) {
		$this->policy_compliance_check = $policy_compliance_check;
		$this->wc                      = $wc;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'ready-but-no-sales';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		if ( ! $this->policy_compliance_check->has_payment_gateways() ) {
			return false;
		}

		if ( empty( $this->wc->get_shipping_zones() ) ) {
			return false;
		}

		return 0 === $this->get_completed_order_count();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::READY_BUT_NO_SALES;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::READY_BUT_NO_SALES;
	}

	/**
	 * Count completed orders.
	 *
	 * @return int
	 */
	protected function get_completed_order_count(): int {
		$orders = wc_get_orders(
			[
				'status' => 'completed',
				'limit'  => 1,
				'return' => 'ids',
			]
		);

		return count( $orders );
	}
}
