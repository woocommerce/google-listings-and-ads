<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20211228T1640692399
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 1.12.2
 */
class Migration20211228T1640692399 extends AbstractMigration {

	/**
	 * @var ShippingRateTable
	 */
	protected $shipping_rate_table;

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * Migration constructor.
	 *
	 * @param wpdb              $wpdb The wpdb object.
	 * @param ShippingRateTable $shipping_rate_table
	 * @param OptionsInterface  $options
	 */
	public function __construct( wpdb $wpdb, ShippingRateTable $shipping_rate_table, OptionsInterface $options ) {
		parent::__construct( $wpdb );
		$this->shipping_rate_table = $shipping_rate_table;
		$this->options             = $options;
	}


	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number. For example: 1.4.1
	 */
	public function get_applicable_version(): string {
		return '1.12.2';
	}

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void {
		if ( $this->shipping_rate_table->exists() ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->shipping_rate_table->get_name() )}` ALTER COLUMN `method` DROP DEFAULT" );

			$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER );
			if ( ! is_array( $mc_settings ) ) {
				return;
			}

			if ( isset( $mc_settings['offers_free_shipping'] ) && false !== boolval( $mc_settings['offers_free_shipping'] ) && isset( $mc_settings['free_shipping_threshold'] ) ) {
				// Move the free shipping threshold from the options to the shipping rate table.
				$options_json = json_encode( [ 'free_shipping_threshold' => (float) $mc_settings['free_shipping_threshold'] ] );
				$this->wpdb->query( $this->wpdb->prepare( "UPDATE `{$this->wpdb->_escape( $this->shipping_rate_table->get_name() )}` SET `options`=%s WHERE `method` = 'flat_rate'", $options_json ) );
			}
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Remove the free shipping threshold from the options.
			unset( $mc_settings['free_shipping_threshold'] );
			unset( $mc_settings['offers_free_shipping'] );
			$this->options->update( OptionsInterface::MERCHANT_CENTER, $mc_settings );
		}
	}
}
