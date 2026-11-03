<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleAccountDisconnectedEvaluator
 *
 * Fires when Merchant Center setup is complete but no Google account is connected, so product
 * sync and campaign management have stopped until the merchant reconnects.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class GoogleAccountDisconnectedEvaluator implements NotificationEvaluatorInterface, Service {

	/** @var MerchantCenterService */
	private $merchant_center;

	/**
	 * GoogleAccountDisconnectedEvaluator constructor.
	 *
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( MerchantCenterService $merchant_center ) {
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'google-account-disconnected';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		return $this->merchant_center->is_setup_complete() && ! $this->merchant_center->is_google_connected();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::GOOGLE_ACCOUNT_DISCONNECTED;
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
