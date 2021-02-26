<?php
/**
 * Global Site Tag functionality - add main script and track conversions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;

/**
 * Main class for Global Site Tag.
 */
class MerchantAccountState extends AccountState {

	/** @var int The number of seconds of delay to enforce between site verification and site claim. */
	public const MC_DELAY_AFTER_CREATE = 90;

	/**
	 * Return the option name.
	 *
	 * @return string
	 */
	protected function option_name(): string {
		return OptionsInterface::MERCHANT_ACCOUNT_STATE;
	}

	/**
	 * Return a list of account creation steps.
	 *
	 * @return string[]
	 */
	protected function account_creation_steps(): array {
		return [ 'set_id', 'verify', 'link', 'claim' ];
	}

	/**
	 * Determine whether the site has already been verified.
	 *
	 * @return bool True if the site is marked as verified.
	 */
	public function is_site_verified(): bool {
		$current_options = $this->options->get( OptionsInterface::SITE_VERIFICATION );

		return ! empty( $current_options['verified'] ) && SiteVerification::VERIFICATION_STATUS_VERIFIED === $current_options['verified'];
	}

	/**
	 * Calculate the number of seconds to wait after creating a sub-account and
	 * before operating on the new sub-account (MCA link and website claim).
	 *
	 * @return int
	 */
	public function get_seconds_to_wait_after_created(): int {
		$state = $this->get( false );

		$created_timestamp = $state['set_id']['data']['created_timestamp'] ?? 0;
		$seconds_elapsed   = time() - $created_timestamp;

		return max( 0, self::MC_DELAY_AFTER_CREATE - $seconds_elapsed );
	}
}
