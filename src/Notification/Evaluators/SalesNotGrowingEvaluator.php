<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use DateTime;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Class SalesNotGrowingEvaluator
 *
 * Fires when GMV for the current calendar month is less than the same month in the prior year.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SalesNotGrowingEvaluator implements NotificationEvaluatorInterface, OptionsAwareInterface, Service {

	use CachedNotificationEvaluatorTrait;
	use OptionsAwareTrait;

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'sales-not-growing';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		if ( ! $this->is_installed_for_at_least_one_year() ) {
			return false;
		}

		$timezone = wp_timezone();
		$now      = new DateTime( 'now', $timezone );

		$current_start = new DateTime( $now->format( 'Y-m-01 00:00:00' ), $timezone );
		$prior_start   = ( clone $current_start )->modify( '-1 year' );
		$prior_end     = ( clone $now )->modify( '-1 year' );

		$current_gmv = $this->get_gmv_for_period( $current_start, $now );
		$prior_gmv   = $this->get_gmv_for_period( $prior_start, $prior_end );

		return $current_gmv < $prior_gmv;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::SALES_NOT_GROWING;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::SALES_NOT_GROWING;
	}

	/**
	 * Whether the plugin has been installed for at least one year.
	 *
	 * @return bool
	 */
	protected function is_installed_for_at_least_one_year(): bool {
		$install_timestamp = $this->options->get( OptionsInterface::INSTALL_TIMESTAMP );

		if ( ! $install_timestamp ) {
			return false;
		}

		return ( time() - (int) $install_timestamp ) >= YEAR_IN_SECONDS;
	}

	/**
	 * Sum GMV for completed orders within a date range.
	 *
	 * @param DateTime $start
	 * @param DateTime $end
	 *
	 * @return float
	 */
	protected function get_gmv_for_period( DateTime $start, DateTime $end ): float {
		$orders = wc_get_orders(
			[
				'status'       => 'completed',
				'date_created' => $start->format( 'Y-m-d H:i:s' ) . '...' . $end->format( 'Y-m-d H:i:s' ),
				'limit'        => -1,
				'return'       => 'objects',
			]
		);

		$gmv = 0.0;

		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$gmv += (float) $order->get_total();
			}
		}

		return $gmv;
	}
}
