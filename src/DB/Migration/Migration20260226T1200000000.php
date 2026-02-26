<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20260226T1200000000
 *
 * Disables API Pull sync mode for all datatypes by setting pull to false in the API_PULL_SYNC_MODE option.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 3.5.3
 */
class Migration20260226T1200000000 extends AbstractMigration {

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * Migration constructor.
	 *
	 * @param wpdb             $wpdb The wpdb object.
	 * @param OptionsInterface $options The options service.
	 */
	public function __construct( wpdb $wpdb, OptionsInterface $options ) {
		parent::__construct( $wpdb );
		$this->options = $options;
	}

	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number.
	 */
	public function get_applicable_version(): string {
		return '3.5.3';
	}

	/**
	 * Apply the migration: set all pull statuses to false in API_PULL_SYNC_MODE.
	 *
	 * @return void
	 */
	public function apply(): void {
		$default_entry = [
			'pull' => false,
			'push' => true,
		];

		$datatypes = [
			NotificationsService::DATATYPE_PRODUCT,
			NotificationsService::DATATYPE_COUPON,
			NotificationsService::DATATYPE_SHIPPING,
			NotificationsService::DATATYPE_SETTINGS,
		];

		$sync_mode = $this->options->get( OptionsInterface::API_PULL_SYNC_MODE );

		if ( ! is_array( $sync_mode ) ) {
			$normalized = array_combine( $datatypes, array_fill( 0, count( $datatypes ), $default_entry ) );
			$this->options->update( OptionsInterface::API_PULL_SYNC_MODE, $normalized );
			return;
		}

		$normalized = [];
		foreach ( $datatypes as $datatype ) {
			$entry = $sync_mode[ $datatype ] ?? $default_entry;
			if ( ! is_array( $entry ) ) {
				$entry = $default_entry;
			}
			$normalized[ $datatype ] = [
				'pull' => false,
				'push' => isset( $entry['push'] ) && is_bool( $entry['push'] ) ? $entry['push'] : true,
			];
		}
		$this->options->update( OptionsInterface::API_PULL_SYNC_MODE, $normalized );
	}
}
