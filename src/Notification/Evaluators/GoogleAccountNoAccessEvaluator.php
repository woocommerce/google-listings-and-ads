<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\InvalidatableNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleAccountNoAccessEvaluator
 *
 * Fires when the connected Google account has no access to the connected Merchant Center
 * and/or Google Ads account, so product sync and campaign management don't work. Stores that
 * set up Google Ads only are included.
 *
 * Only a missing permission counts as no access: an access check that fails for another
 * reason (a timeout, a rate limit, a Google outage) doesn't show the notification.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class GoogleAccountNoAccessEvaluator implements CachedNotificationEvaluatorInterface, InvalidatableNotificationEvaluatorInterface, Service {

	use CachedNotificationEvaluatorTrait {
		should_show as protected get_stored_result;
		needs_refresh as protected stored_result_needs_refresh;
	}

	/** @var MerchantCenterService */
	private $merchant_center;

	/** @var AdsService */
	private $ads;

	/** @var Connection */
	private $connection;

	/**
	 * GoogleAccountNoAccessEvaluator constructor.
	 *
	 * @param MerchantCenterService $merchant_center
	 * @param AdsService            $ads
	 * @param Connection            $connection
	 */
	public function __construct( MerchantCenterService $merchant_center, AdsService $ads, Connection $connection ) {
		$this->merchant_center = $merchant_center;
		$this->ads             = $ads;
		$this->connection      = $connection;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'google-account-no-access';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * Checked before the stored result, so the notification hides as soon as the store
	 * disconnects, without waiting for a refresh. A result invalidated since it was computed
	 * describes the previous accounts, so it stays hidden until the refresh checks the new ones.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		return $this->is_set_up_and_connected() && ! $this->is_invalidated_since_computed() && $this->get_stored_result();
	}

	/**
	 * Whether the stored result is missing or stale and should be recalculated.
	 *
	 * A store that isn't set up and connected never needs the remote access check.
	 *
	 * @return bool
	 */
	public function needs_refresh(): bool {
		return $this->is_set_up_and_connected() && $this->stored_result_needs_refresh();
	}

	/**
	 * Whether the stored result was invalidated at or after the time it was computed.
	 *
	 * @return bool
	 */
	private function is_invalidated_since_computed(): bool {
		$cached = get_transient( $this->get_cache_key() );
		if ( ! is_array( $cached ) ) {
			return false;
		}

		return (int) ( $cached['invalidated_at'] ?? 0 ) >= (int) ( $cached['computed_at'] ?? 0 );
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		if ( ! $this->is_set_up_and_connected() ) {
			return false;
		}

		try {
			$status = $this->connection->get_access_status();
		} catch ( Exception $e ) {
			// The connection status is unknown, which says nothing about access.
			return false;
		}

		if ( 'connected' !== ( $status['status'] ?? '' ) ) {
			return false;
		}

		return false === ( $status['merchant_access'] ?? null ) || false === ( $status['ads_access'] ?? null );
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::GOOGLE_ACCOUNT_NO_ACCESS;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return NotificationSnoozeDurations::UNTIL_NEXT_LOGIN;
	}

	/**
	 * Connecting, disconnecting or switching the Google account, the Merchant Center account or
	 * the Google Ads account changes which accounts the access check applies to.
	 *
	 * Uses core's option hooks because update_option_{$option} only fires when the value changes;
	 * Connection::get_status() rewrites the same Google connection value on every status check.
	 * The hook names include the stored option name, so the "gla_" prefix here must match the one
	 * Options::prefix_name() builds from the plugin slug.
	 *
	 * @return string[]
	 */
	public function get_invalidation_hooks(): array {
		$hooks = [ 'update_option_gla_' . OptionsInterface::GOOGLE_CONNECTED ];

		foreach ( [ OptionsInterface::MERCHANT_ID, OptionsInterface::ADS_ID ] as $account_option ) {
			$hooks[] = 'add_option_gla_' . $account_option;
			$hooks[] = 'update_option_gla_' . $account_option;
			$hooks[] = 'delete_option_gla_' . $account_option;
		}

		return $hooks;
	}

	/**
	 * Whether Merchant Center or Google Ads setup is complete and a Google account is connected.
	 *
	 * @return bool
	 */
	private function is_set_up_and_connected(): bool {
		return ( $this->merchant_center->is_setup_complete() || $this->ads->is_setup_complete() ) && $this->merchant_center->is_google_connected();
	}
}
