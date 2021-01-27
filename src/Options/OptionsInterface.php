<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

/**
 * Interface OptionsInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
interface OptionsInterface {

	public const DB_VERSION            = 'db_version';
	public const FILE_VERSION          = 'file_version';
	public const INSTALL_TIMESTAMP     = 'install_timestamp';
	public const MC_SETUP_COMPLETED_AT = 'mc_setup_completed_at';
	public const MERCHANT_CENTER       = 'merchant_center';
	public const MERCHANT_ID           = 'merchant_id';
	public const SHIPPING_RATES        = 'shipping_rates';
	public const SHIPPING_TIMES        = 'shipping_times';
	public const ADS_ID                = 'ads_id';
	public const SITE_VERIFICATION     = 'site_verification';

	/**
	 * Get an option.
	 *
	 * @param string $name    The option name.
	 * @param mixed  $default A default value for the option.
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = null );

	/**
	 * Add an option.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The option value.
	 *
	 * @return bool
	 */
	public function add( string $name, $value ): bool;

	/**
	 * Update an option.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The option value.
	 *
	 * @return bool
	 */
	public function update( string $name, $value ): bool;

	/**
	 * Delete an option.
	 *
	 * @param string $name The option name.
	 *
	 * @return bool
	 */
	public function delete( string $name ): bool;
}
