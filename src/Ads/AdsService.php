<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AdsService implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;

	/**
	 * Get whether Ads setup is completed.
	 *
	 * @return bool
	 */
	public function is_setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::ADS_SETUP_COMPLETED_AT, false ) );
	}

	/**
	 * Get whether Ads is connected.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		$google_connected = boolval( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) );
		return $google_connected && $this->is_setup_complete();
	}

	/**
	 * Disconnect Ads account
	 */
	public function disconnect() {
		$this->options->delete( OptionsInterface::ADS_ACCOUNT_CURRENCY );
		$this->options->delete( OptionsInterface::ADS_ACCOUNT_STATE );
		$this->options->delete( OptionsInterface::ADS_BILLING_URL );
		$this->options->delete( OptionsInterface::ADS_CONVERSION_ACTION );
		$this->options->delete( OptionsInterface::ADS_ID );
		$this->options->delete( OptionsInterface::ADS_SETUP_COMPLETED_AT );
	}
}
