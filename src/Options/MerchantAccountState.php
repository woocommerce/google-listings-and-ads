<?php
/**
 * Global Site Tag functionality - add main script and track conversions.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Main class for Global Site Tag.
 */
class MerchantAccountState implements Service {

	use OptionsAwareTrait;

	/**
	 * @var string[]
	 */
	public const MERCHANT_ACCOUNT_CREATION_STEPS = [ 'set_id', 'verify', 'link', 'claim' ];

	/** @var int Status value for a pending merchant account creation step */
	public const ACCOUNT_STEP_PENDING = 0;

	/** @var int Status value for a completed merchant account creation step */
	public const ACCOUNT_STEP_DONE = 1;

	/** @var int Status value for an unsuccessful merchant account creation step */
	public const ACCOUNT_STEP_ERROR = - 1;

	/** @var int The number of seconds of delay to enforce between site verification and site claim. */
	public const MC_DELAY_AFTER_CREATE = 90;

	/**
	 * MerchantAccountState constructor.
	 *
	 * @param OptionsInterface $options
	 */
	public function __construct( OptionsInterface $options ) {
		$this->set_options_object( $options );
	}

	/**
	 * Retrieve or initialize the MERCHANT_ACCOUNT_STATE Option.
	 *
	 * @param bool $initialize_if_not_found True to initialize and option array of steps.
	 *
	 * @return array The MC creation steps and statuses.
	 */
	public function get( bool $initialize_if_not_found = true ): array {
		$state = $this->options->get( OptionsInterface::MERCHANT_ACCOUNT_STATE );
		if ( empty( $state ) && $initialize_if_not_found ) {
			$state = [];
			foreach ( self::MERCHANT_ACCOUNT_CREATION_STEPS as $step ) {
				$state[ $step ] = [
					'status'  => self::ACCOUNT_STEP_PENDING,
					'message' => '',
					'data'    => [],
				];
			}
			$this->update( $state );
		}

		return $state;
	}

	/**
	 * Update the MERCHANT_ACCOUNT_STATE Option.
	 *
	 * @param array $state
	 */
	public function update( array $state ) {
		$this->options->update( OptionsInterface::MERCHANT_ACCOUNT_STATE, $state );
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
