<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductIssuesEvaluator
 *
 * Fires when the MC statuses cache contains at least one disapproved product.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class ProductIssuesEvaluator implements NotificationEvaluatorInterface, MerchantCenterAwareInterface, TransientsAwareInterface, Service {

	use MerchantCenterAwareTrait;
	use TransientsAwareTrait;

	/** @var ServiceBasedMerchantState */
	private $service_based_merchant_state;

	/**
	 * ProductIssuesEvaluator constructor.
	 *
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 */
	public function __construct( ServiceBasedMerchantState $service_based_merchant_state ) {
		$this->service_based_merchant_state = $service_based_merchant_state;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'product-issues';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( $this->service_based_merchant_state->is_service_based_merchant() ) {
			return false;
		}

		if ( ! $this->merchant_center->is_connected() ) {
			return false;
		}

		$mc_statuses = $this->transients->get( TransientsInterface::MC_STATUSES );

		if ( ! is_array( $mc_statuses ) ) {
			return false;
		}

		$statistics = $mc_statuses['statistics'] ?? [];

		return ( $statistics[ MCStatus::DISAPPROVED ] ?? 0 ) > 0;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::PRODUCT_ISSUES;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::UNTIL_NEXT_LOGIN;
	}
}
