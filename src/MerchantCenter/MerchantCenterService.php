<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterService
 *
 * ContainerAware used to access:
 * - MerchantAccountState
 * - MerchantStatuses
 * - ShippingRateTable
 * - ShippingTimeTable
 * - TransientsInterface
 * - WC
 * - WP
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MerchantCenterService implements ContainerAwareInterface, OptionsAwareInterface, Service {

	use GoogleHelper;
	use OptionsAwareTrait;
	use ContainerAwareTrait;

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	public function is_setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::MC_SETUP_COMPLETED_AT, false ) );
	}

	/**
	 * Get whether Merchant Center is connected.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		$google_connected = boolval( $this->options->get( OptionsInterface::GOOGLE_CONNECTED, false ) );
		return $google_connected && $this->is_setup_complete();
	}

	/**
	 * Get whether the country is supported by the Merchant Center.
	 *
	 * @param  string $country Optional - to check a country other than the site country.
	 * @return bool True if the country is in the list of MC-supported countries.
	 */
	public function is_country_supported( string $country = '' ): bool {
		// Default to WooCommerce store country
		if ( empty( $country ) ) {
			$country = $this->container->get( WC::class )->get_base_country();
		}

		return array_key_exists(
			strtoupper( $country ),
			$this->get_mc_supported_countries_currencies()
		);
	}

	/**
	 * Get whether the language is supported by the Merchant Center.
	 *
	 * @param  string $language Optional - to check a language other than the site language.
	 * @return bool True if the language is in the list of MC-supported languages.
	 */
	public function is_language_supported( string $language = '' ): bool {
		// Default to base site language
		if ( empty( $language ) ) {
			$language = substr( $this->container->get( WP::class )->get_locale(), 0, 2 );
		}

		return array_key_exists(
			strtolower( $language ),
			$this->get_mc_supported_languages()
		);
	}

	/**
	 * @return string[] List of target countries specified in options. Defaults to WooCommerce store base country.
	 */
	public function get_target_countries(): array {
		$target_countries = [ $this->container->get( WC::class )->get_base_country() ];

		$target_audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );
		if ( empty( $target_audience['location'] ) && empty( $target_audience['countries'] ) ) {
			return $target_countries;
		}

		$location = strtolower( $target_audience['location'] );
		if ( 'all' === $location ) {
			$target_countries = $this->get_mc_supported_countries();
		} elseif ( 'selected' === $location && ! empty( $target_audience['countries'] ) ) {
			$target_countries = $target_audience['countries'];
		}

		return $target_countries;
	}

	/**
	 * Get the connected merchant account.
	 *
	 * @return array
	 */
	public function get_connected_status(): array {
		$id     = $this->options->get_merchant_id();
		$status = [
			'id'     => $id,
			'status' => $id ? 'connected' : 'disconnected',
		];

		$incomplete = $this->container->get( MerchantAccountState::class )->last_incomplete_step();
		if ( ! empty( $incomplete ) ) {
			$status['status'] = 'incomplete';
			$status['step']   = $incomplete;
		}

		return $status;
	}

	/**
	 * Return the setup status to determine what step to continue at.
	 *
	 * @return array
	 */
	public function get_setup_status(): array {
		if ( $this->is_setup_complete() ) {
			return [ 'status' => 'complete' ];
		}

		$step = 'accounts';
		if ( $this->connected_account() ) {
			$step = 'target_audience';

			if ( $this->saved_target_audience() ) {
				$step = 'shipping_and_taxes';
			}
		}

		return [
			'status' => 'incomplete',
			'step'   => $step,
		];
	}

	/**
	 * Disconnect Merchant Center account
	 */
	public function disconnect() {
		$this->options->delete( OptionsInterface::MC_SETUP_COMPLETED_AT );
		$this->options->delete( OptionsInterface::MERCHANT_ACCOUNT_STATE );
		$this->options->delete( OptionsInterface::MERCHANT_CENTER );
		$this->options->delete( OptionsInterface::SITE_VERIFICATION );
		$this->options->delete( OptionsInterface::TARGET_AUDIENCE );
		$this->options->delete( OptionsInterface::MERCHANT_ID );

		$this->container->get( MerchantStatuses::class )->delete();

		$this->container->get( ShippingRateTable::class )->truncate();
		$this->container->get( ShippingTimeTable::class )->truncate();
	}

	/**
	 * Check if account has been connected.
	 *
	 * @return bool
	 */
	protected function connected_account(): bool {
		$id = $this->options->get_merchant_id();
		return $id && ! $this->container->get( MerchantAccountState::class )->last_incomplete_step();
	}

	/**
	 * Check if target audience has been saved (with a valid selection of countries).
	 *
	 * @return bool
	 */
	protected function saved_target_audience(): bool {
		$audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );

		if ( empty( $audience ) || ! isset( $audience['location'] ) ) {
			return false;
		}

		$empty_selection = 'selected' === $audience['location'] && empty( $audience['countries'] );
		return ! $empty_selection;
	}
}
