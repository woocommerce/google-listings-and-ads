<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20260226T1200000000
 *
 * Disables API Pull sync mode by setting pull to false wherever it exists in the API_PULL_SYNC_MODE option.
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
	 * Apply the migration: set pull to false for any existing pull status in API_PULL_SYNC_MODE.
	 *
	 * @return void
	 */
	public function apply(): void {
		$sync_mode = $this->options->get( OptionsInterface::API_PULL_SYNC_MODE );

		// Let filters and pre_update_option handle the default values.
		if ( ! is_array( $sync_mode ) ) {
			return;
		}

		// Only set 'pull' to false for existing entries that have a 'pull' key.
		foreach ( $sync_mode as $key => $entry ) {
			if ( is_array( $entry ) && array_key_exists( 'pull', $entry ) ) {
				if ( true === $sync_mode[ $key ]['pull'] ) {
					// Enable push sync mode if pull was enabled to keep store synced.
					$sync_mode[ $key ] = [
						'pull' => false,
						'push' => true,
					];
				}
			}
		}

		$this->options->update( OptionsInterface::API_PULL_SYNC_MODE, $sync_mode );
	}
}
