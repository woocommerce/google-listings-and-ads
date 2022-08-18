<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;

/**
 * Interface OptionsInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
interface OptionsInterface {

	public const ADS_ACCOUNT_CURRENCY    = 'ads_account_currency';
	public const ADS_ACCOUNT_STATE       = 'ads_account_state';
	public const ADS_BILLING_URL         = 'ads_billing_url';
	public const ADS_ID                  = 'ads_id';
	public const ADS_CONVERSION_ACTION   = 'ads_conversion_action';
	public const ADS_SETUP_COMPLETED_AT  = 'ads_setup_completed_at';
	public const CAMPAIGN_CONVERT_STATUS = 'campaign_convert_status';
	public const CLAIMED_URL_HASH        = 'claimed_url_hash';
	public const CONTACT_INFO_SETUP      = 'contact_info_setup';
	public const DELAYED_ACTIVATE        = 'delayed_activate';
	public const DB_VERSION              = 'db_version';
	public const FILE_VERSION            = 'file_version';
	public const GOOGLE_CONNECTED        = 'google_connected';
	public const INSTALL_TIMESTAMP       = 'install_timestamp';
	public const JETPACK_CONNECTED       = 'jetpack_connected';
	public const MC_SETUP_COMPLETED_AT   = 'mc_setup_completed_at';
	public const MERCHANT_ACCOUNT_STATE  = 'merchant_account_state';
	public const MERCHANT_CENTER         = 'merchant_center';
	public const MERCHANT_ID             = 'merchant_id';
	public const REDIRECT_TO_ONBOARDING  = 'redirect_to_onboarding';
	public const SHIPPING_RATES          = 'shipping_rates';
	public const SHIPPING_TIMES          = 'shipping_times';
	public const SITE_VERIFICATION       = 'site_verification';
	public const TARGET_AUDIENCE         = 'target_audience';
	public const WP_TOS_ACCEPTED         = 'wp_tos_accepted';

	public const VALID_OPTIONS = [
		self::ADS_ACCOUNT_CURRENCY    => true,
		self::ADS_ACCOUNT_STATE       => true,
		self::ADS_BILLING_URL         => true,
		self::ADS_ID                  => true,
		self::ADS_CONVERSION_ACTION   => true,
		self::ADS_SETUP_COMPLETED_AT  => true,
		self::CAMPAIGN_CONVERT_STATUS => true,
		self::CLAIMED_URL_HASH        => true,
		self::CONTACT_INFO_SETUP      => true,
		self::DB_VERSION              => true,
		self::FILE_VERSION            => true,
		self::GOOGLE_CONNECTED        => true,
		self::INSTALL_TIMESTAMP       => true,
		self::JETPACK_CONNECTED       => true,
		self::MC_SETUP_COMPLETED_AT   => true,
		self::MERCHANT_ACCOUNT_STATE  => true,
		self::MERCHANT_CENTER         => true,
		self::MERCHANT_ID             => true,
		self::DELAYED_ACTIVATE        => true,
		self::SHIPPING_RATES          => true,
		self::SHIPPING_TIMES          => true,
		self::REDIRECT_TO_ONBOARDING  => true,
		self::SITE_VERIFICATION       => true,
		self::TARGET_AUDIENCE         => true,
		self::WP_TOS_ACCEPTED         => true,
	];

	public const OPTION_TYPES = [
		self::ADS_ID      => PositiveInteger::class,
		self::MERCHANT_ID => PositiveInteger::class,
	];

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

	/**
	 * Helper function to retrieve the Merchant Account ID.
	 *
	 * @return int
	 */
	public function get_merchant_id(): int;

	/**
	 * Returns all available option keys.
	 *
	 * @return array
	 */
	public static function get_all_option_keys(): array;

	/**
	 * Helper function to retrieve the Ads Account ID.
	 *
	 * @return int
	 */
	public function get_ads_id(): int;
}
