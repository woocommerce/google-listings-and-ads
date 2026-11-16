<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantAccessCircuitBreaker
 *
 * Pauses syncing after the Merchant API reports that the connected Google account has no
 * access to the Merchant Center account.
 *
 * The Google account stays connected in that case, so without a pause every product save and
 * sync job would keep calling the Merchant API and getting the same rejection. A rejection
 * pauses syncing for one hour; the next attempt after the window is a single probe. The pause
 * ends early when an access check confirms access, or when the connected Google or Merchant
 * Center account changes.
 *
 * The pause is stored in an option rather than a transient so that an object cache
 * eviction cannot silently drop it.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantAccessCircuitBreaker implements OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;

	/**
	 * End the pause when the connected Google or Merchant Center account changes.
	 *
	 * The hook names include the stored option name, so the "gla_" prefix here must match the
	 * one Options::prefix_name() builds from the plugin slug.
	 */
	public function register(): void {
		$hooks = [ 'update_option_gla_' . OptionsInterface::GOOGLE_CONNECTED ];

		foreach ( [ 'add_option_gla_', 'update_option_gla_', 'delete_option_gla_' ] as $prefix ) {
			$hooks[] = $prefix . OptionsInterface::MERCHANT_ID;
		}

		foreach ( $hooks as $hook ) {
			add_action(
				$hook,
				function () {
					$this->reset();
				}
			);
		}
	}

	/**
	 * Record a rejection because the Google account has no access to the Merchant Center account.
	 */
	public function trip(): void {
		// A rejection while already open (e.g. from an admin page load) keeps the current window.
		if ( ! $this->is_open() ) {
			$this->options->update( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, time() );
		}
	}

	/**
	 * End the pause.
	 */
	public function reset(): void {
		if ( $this->options->get( OptionsInterface::MERCHANT_ACCESS_DENIED_AT ) ) {
			$this->options->delete( OptionsInterface::MERCHANT_ACCESS_DENIED_AT );
		}
	}

	/**
	 * Whether the breaker is open, i.e. syncing is paused.
	 *
	 * @return bool
	 */
	public function is_open(): bool {
		$denied_at = (int) $this->options->get( OptionsInterface::MERCHANT_ACCESS_DENIED_AT, 0 );

		return $denied_at > 0 && $denied_at + HOUR_IN_SECONDS > time();
	}
}
