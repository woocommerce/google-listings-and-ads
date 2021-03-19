<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantSetupCompleted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class MerchantSetupCompleted implements OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;

	protected const OPTION = OptionsInterface::MC_SETUP_COMPLETED_AT;

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'gla_mc_settings_sync',
			function() {
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
}
