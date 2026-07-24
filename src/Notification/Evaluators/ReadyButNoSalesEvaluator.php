<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\RevenueOrdersTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReadyButNoSalesEvaluator
 *
 * Fires when at least one payment method and one shipping method are configured and the store has zero sales.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class ReadyButNoSalesEvaluator implements NotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait;
	use RevenueOrdersTrait;

	/** @var WC */
	private $wc;

	/**
	 * ReadyButNoSalesEvaluator constructor.
	 *
	 * @param WC $wc
	 */
	public function __construct( WC $wc ) {
		$this->wc = $wc;
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
		if ( ! $this->wc->has_enabled_payment_gateways() ) {
			return false;
		}

		if ( ! $this->store_has_any_enabled_shipping_method() ) {
			return false;
		}

		return ! $this->has_minimum_revenue_orders( 1 );
	}

	/**
	 * Whether the store has at least one enabled shipping method in any zone.
	 *
	 * Includes the default zone (id 0), which is omitted from get_shipping_zones().
	 *
	 * @return bool
	 */
	protected function store_has_any_enabled_shipping_method(): bool {
		foreach ( $this->wc->get_shipping_zones() as $zone_data ) {
			$zone = $this->wc->get_shipping_zone( (int) $zone_data['zone_id'] );

			if ( $zone && ! empty( $zone->get_shipping_methods( true ) ) ) {
				return true;
			}
		}

		$default_zone = $this->wc->get_shipping_zone( 0 );

		return $default_zone && ! empty( $default_zone->get_shipping_methods( true ) );
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
}
