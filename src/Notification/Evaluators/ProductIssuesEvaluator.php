<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
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
class ProductIssuesEvaluator implements NotificationEvaluatorInterface, TransientsAwareInterface, Service {

	use TransientsAwareTrait;

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
		$mc_statuses = $this->transients->get( TransientsInterface::MC_STATUSES );

		if ( ! is_array( $mc_statuses ) || empty( $mc_statuses['statistics'] ) ) {
			return false;
		}

		foreach ( $mc_statuses['statistics'] as $status => $count ) {
			if ( MCStatus::DISAPPROVED === $status && $count > 0 ) {
				return true;
			}
		}

		return false;
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
		return null;
	}
}
