<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class OnboardingCompleted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class OnboardingCompleted implements OptionsAwareInterface, MerchantCenterAwareInterface, Registerable, Service {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	protected const OPTION = OptionsInterface::ONBOARDING_COMPLETED_AT;

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'woocommerce_gla_onboarding_completed',
			function () {
				$this->set_completed_timestamp();
			}
		);
	}

	/**
	 * Set the timestamp when setup was completed.
	 */
	protected function set_completed_timestamp() {
		$this->options->update( self::OPTION, time() );
	}

	/**
	 * Check if onboarding is complete.
	 * For backwards compatibility, checks MC setup if ONBOARDING_COMPLETED_AT is not set.
	 *
	 * @return bool
	 */
	public function is_onboarding_complete(): bool {
		$onboarding_completed_at = $this->options->get( OptionsInterface::ONBOARDING_COMPLETED_AT );

		if ( null !== $onboarding_completed_at ) {
			return boolval( $onboarding_completed_at );
		}

		if ( $this->merchant_center->is_setup_complete() ) {
			$mc_setup_completed_at = $this->options->get( OptionsInterface::MC_SETUP_COMPLETED_AT );
			$this->options->update( OptionsInterface::ONBOARDING_COMPLETED_AT, $mc_setup_completed_at );
			return true;
		}

		return false;
	}
}
