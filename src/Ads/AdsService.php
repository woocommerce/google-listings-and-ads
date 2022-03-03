<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
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

	/** @var AdsAccountState */
	protected $account_state;

	/**
	 * AdsService constructor.
	 *
	 * @since 1.11.0
	 *
	 * @param AdsAccountState $account_state
	 */
	public function __construct( AdsAccountState $account_state ) {
		$this->account_state = $account_state;
	}

	/**
	 * Determine whether Ads setup has been started.
	 *
	 * @since 1.11.0
	 * @return bool
	 */
	public function is_setup_started(): bool {
		return $this->account_state->last_incomplete_step() !== '' && ! $this->is_setup_complete();
	}

	/**
	 * Determine whether Ads setup has completed.
	 *
	 * @return bool
	 */
	public function is_setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::ADS_SETUP_COMPLETED_AT, false ) );
	}

	/**
	 * Determine whether Ads has connected.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		$google_connected = boolval( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) );
		return $google_connected && $this->is_setup_complete();
	}

}
