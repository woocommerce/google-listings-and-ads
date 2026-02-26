<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncStatus
 *
 * Ensures API Pull sync mode is disabled for all users. Registers filters and
 * pre_update_option to force pull status to false for all datatypes.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class SyncStatus implements Service, Registerable {

	use PluginHelper;

	/**
	 * Register filters and the pre_update_option hook.
	 */
	public function register(): void {
		add_filter( 'woocommerce_gla_sync_mode', [ $this, 'force_pull_false_in_sync_mode' ], PHP_INT_MAX, 1 );
		add_filter( 'woocommerce_gla_is_pull_enabled_for_datatype', [ $this, 'force_pull_disabled_for_datatype' ], PHP_INT_MAX, 2 );
		add_filter(
			'pre_update_option_' . $this->get_slug() . '_' . OptionsInterface::API_PULL_SYNC_MODE,
			[ $this, 'normalize_api_pull_sync_mode_on_update' ],
			PHP_INT_MAX,
			3
		);
	}

	/**
	 * Force pull => false for every datatype entry that has a pull key.
	 *
	 * @param array $sync_mode The current sync mode array.
	 * @return array Sync mode with pull false where it existed.
	 */
	public function force_pull_false_in_sync_mode( array $sync_mode ): array {
		foreach ( $sync_mode as $key => $entry ) {
			if ( is_array( $entry ) && array_key_exists( 'pull', $entry ) ) {
				$sync_mode[ $key ]['pull'] = false;
			}
		}
		return $sync_mode;
	}

	/**
	 * Always return false for pull enabled for any datatype.
	 *
	 * @param bool   $pull_enabled The current value (ignored).
	 * @param string $data_type    The data type (ignored).
	 * @return bool Always false.
	 */
	public function force_pull_disabled_for_datatype( $pull_enabled, string $data_type ): bool {
		return false;
	}

	/**
	 * Normalize the API_PULL_SYNC_MODE value on update: set any existing pull to false.
	 *
	 * @param mixed  $value     New value being saved.
	 * @param mixed  $old_value Previous value.
	 * @param string $option    Option name.
	 * @return mixed Value with pull false where it existed, unchanged otherwise.
	 */
	public function normalize_api_pull_sync_mode_on_update( $value, $old_value, string $option ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $entry ) {
			if ( is_array( $entry ) && array_key_exists( 'pull', $entry ) ) {
				$value[ $key ]['pull'] = false;
			}
		}
		return $value;
	}
}
