<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

/**
 * Include Google Listings and Ads data in the WC Tracker snapshot.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class TrackerSnapshot implements Conditional, OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;
	use PluginHelper;

	/**
	 * Not needed if allow_tracking is disabled.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
	}

	/**
	 * Hook extension tracker data into the WC tracker data.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_tracker_data',
			function( $data ) {
				return $this->include_snapshot_data( $data );
			}
		);
	}

	/**
	 * Add extension data to the WC Tracker snapshot.
	 *
	 * @param array $data The existing array of tracker data.
	 *
	 * @return array The updated array of tracker data.
	 */
	protected function include_snapshot_data( $data = [] ): array {
		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = [];
		}

		$data['extensions'][ $this->get_slug() ] = [
			'settings' => $this->get_settings(),
		];

		return $data;
	}

	/**
	 * Get general extension and settings data for the extension.
	 *
	 * @return array
	 */
	protected function get_settings(): array {
		return [
			'version'          => $this->get_version(),
			'db_version'       => $this->options->get( OptionsInterface::DB_VERSION ),
			'tos_accepted'     => $this->get_boolean_value( OptionsInterface::WP_TOS_ACCEPTED ),
			'google_connected' => $this->get_boolean_value( OptionsInterface::GOOGLE_CONNECTED ),
			'mc_setup'         => $this->get_boolean_value( OptionsInterface::MC_SETUP_COMPLETED_AT ),
			'ads_setup'        => $this->get_boolean_value( OptionsInterface::ADS_SETUP_COMPLETED_AT ),
			'target_audience'  => $this->get_target_countries(),
		];
	}

	/**
	 * Get boolean value from options, return as yes or no.
	 *
	 * @param string $key Option key name.
	 *
	 * @return string
	 */
	protected function get_boolean_value( string $key ): string {
		return (bool) $this->options->get( $key ) ? 'yes' : 'no';
	}

	/**
	 * Get a comma separated list of target country codes.
	 * Returns "all" when all countries are selected.
	 *
	 * @return string
	 */
	protected function get_target_countries(): string {
		$target_audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );
		if ( empty( $target_audience['location'] ) && empty( $target_audience['countries'] ) ) {
			return '';
		}

		$countries = '';
		$location  = strtolower( $target_audience['location'] );
		if ( 'all' === $location ) {
			$countries = 'all';
		} elseif ( 'selected' === $location && ! empty( $target_audience['countries'] ) ) {
			$countries = implode( ',', $target_audience['countries'] );
		}

		return $countries;
	}

}
