<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterAvailableEvaluator
 *
 * Fires when a store that onboarded as service-based has since added physical
 * products, and Merchant Center is not connected yet.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class MerchantCenterAvailableEvaluator implements NotificationEvaluatorInterface, Service {

	/** @var ServiceBasedMerchantState */
	private $service_based_merchant_state;

	/** @var MerchantCenterService */
	private $merchant_center;

	/**
	 * MerchantCenterAvailableEvaluator constructor.
	 *
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 * @param MerchantCenterService     $merchant_center
	 */
	public function __construct( ServiceBasedMerchantState $service_based_merchant_state, MerchantCenterService $merchant_center ) {
		$this->service_based_merchant_state = $service_based_merchant_state;
		$this->merchant_center              = $merchant_center;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'merchant-center-available';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( ! $this->service_based_merchant_state->has_switched_to_physical_products() ) {
			return false;
		}

		if ( $this->merchant_center->is_connected() ) {
			return false;
		}

		// Only a recalculation of the classification clears the switch record, and removing a
		// product just resets the cached classification. Reading it here runs that recalculation,
		// so a store with no physical products left never sees this notification.
		return ! $this->service_based_merchant_state->is_service_based_merchant();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::MERCHANT_CENTER_AVAILABLE;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null Null, so a dismissal is permanent.
	 */
	public function get_snooze_duration(): ?int {
		return null;
	}
}
