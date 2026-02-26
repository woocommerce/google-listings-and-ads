<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
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
	 * Datatypes that have sync mode (products, coupons, shipping, settings).
	 *
	 * @var string[]
	 */
	private const DATATYPES = [
		NotificationsService::DATATYPE_PRODUCT,
		NotificationsService::DATATYPE_COUPON,
		NotificationsService::DATATYPE_SHIPPING,
		NotificationsService::DATATYPE_SETTINGS,
	];

	/**
	 * Register filters and the pre_update_option hook.
	 */
	public function register(): void {
		add_filter( 'woocommerce_gla_sync_mode', [ $this, 'force_pull_false_in_sync_mode' ], 10, 1 );
		add_filter( 'woocommerce_gla_is_pull_enabled_for_datatype', [ $this, 'force_pull_disabled_for_datatype' ], 10, 2 );
		add_filter(
			'pre_update_option_' . $this->get_slug() . '_' . OptionsInterface::API_PULL_SYNC_MODE,
			[ $this, 'normalize_api_pull_sync_mode_on_update' ],
			10,
			3
		);
	}

	/**
	 * Force every datatype in the sync mode array to have pull => false.
	 *
	 * @param array $sync_mode The current sync mode array.
	 * @return array Sync mode with pull false for all datatypes.
	 */
	public function force_pull_false_in_sync_mode( array $sync_mode ): array {
		foreach ( self::DATATYPES as $datatype ) {
			if ( isset( $sync_mode[ $datatype ] ) && is_array( $sync_mode[ $datatype ] ) ) {
				$sync_mode[ $datatype ]['pull'] = false;
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
	 * Normalize the API_PULL_SYNC_MODE value on update: set pull to false for all datatypes.
	 *
	 * @param mixed  $value     New value being saved.
	 * @param mixed  $old_value Previous value.
	 * @param string $option    Option name.
	 * @return array Normalized value with pull false for all datatypes.
	 */
	public function normalize_api_pull_sync_mode_on_update( $value, $old_value, string $option ): array {
		$default_entry = [
			'pull' => false,
			'push' => true,
		];

		if ( ! is_array( $value ) ) {
			return array_combine( self::DATATYPES, array_fill( 0, count( self::DATATYPES ), $default_entry ) );
		}

		$normalized = [];
		foreach ( self::DATATYPES as $datatype ) {
			$entry = $value[ $datatype ] ?? $default_entry;
			if ( ! is_array( $entry ) ) {
				$entry = $default_entry;
			}
			$normalized[ $datatype ] = [
				'pull' => false,
				'push' => isset( $entry['push'] ) && is_bool( $entry['push'] ) ? $entry['push'] : true,
			];
		}
		return $normalized;
	}
}
