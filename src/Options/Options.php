<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidOption;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\CastableValueInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ValueInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Options
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
final class Options implements OptionsInterface, Service {

	use PluginHelper;

	private const VALID_OPTIONS = [
		self::ADS_ACCOUNT_STATE      => true,
		self::ADS_BILLING_URL        => true,
		self::ADS_ID                 => true,
		self::ADS_CONVERSION_ACTION  => true,
		self::ADS_SETUP_COMPLETED_AT => true,
		self::DB_VERSION             => true,
		self::FILE_VERSION           => true,
		self::GOOGLE_CONNECTED       => true,
		self::INSTALL_TIMESTAMP      => true,
		self::MC_SETUP_COMPLETED_AT  => true,
		self::MERCHANT_ACCOUNT_STATE => true,
		self::MERCHANT_CENTER        => true,
		self::MERCHANT_ID            => true,
		self::SHIPPING_RATES         => true,
		self::SHIPPING_TIMES         => true,
		self::REDIRECT_TO_ONBOARDING => true,
		self::SITE_VERIFICATION      => true,
		self::TARGET_AUDIENCE        => true,
		self::WP_TOS_ACCEPTED        => true,
	];

	private const OPTION_TYPES = [
		self::ADS_ID      => PositiveInteger::class,
		self::MERCHANT_ID => PositiveInteger::class,
	];

	/**
	 * Array of options that we have loaded.
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * Get an option.
	 *
	 * @param string $name    The option name.
	 * @param mixed  $default A default value for the option.
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = null ) {
		$this->validate_option_key( $name );

		if ( ! array_key_exists( $name, $this->options ) ) {
			$value                  = get_option( $this->prefix_name( $name ), $default );
			$this->options[ $name ] = $this->maybe_cast_value( $name, $value );
		}

		return $this->raw_value( $this->options[ $name ] );
	}

	/**
	 * Add an option.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The option value.
	 *
	 * @return bool
	 */
	public function add( string $name, $value ): bool {
		$this->validate_option_key( $name );
		$value                  = $this->maybe_convert_value( $name, $value );
		$this->options[ $name ] = $value;

		$result = add_option( $this->prefix_name( $name ), $this->raw_value( $value ) );

		do_action( "gla_options_updated_{$name}", $value );

		return $result;
	}

	/**
	 * Update an option.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The option value.
	 *
	 * @return bool
	 */
	public function update( string $name, $value ): bool {
		$this->validate_option_key( $name );
		$value                  = $this->maybe_convert_value( $name, $value );
		$this->options[ $name ] = $value;

		$result = update_option( $this->prefix_name( $name ), $this->raw_value( $value ) );

		do_action( "gla_options_updated_{$name}", $value );

		return $result;
	}

	/**
	 * Delete an option.
	 *
	 * @param string $name The option name.
	 *
	 * @return bool
	 */
	public function delete( string $name ): bool {
		$this->validate_option_key( $name );
		unset( $this->options[ $name ] );

		$result = delete_option( $this->prefix_name( $name ) );

		do_action( "gla_options_deleted_{$name}" );

		return $result;
	}

	/**
	 * Helper function to retrieve the Ads Account ID.
	 *
	 * @return int
	 */
	public function get_ads_id(): int {
		// TODO: Remove overriding with default once ConnectionTest is removed.
		$default = intval( $_GET['customer_id'] ?? 0 ); // phpcs:ignore WordPress.Security
		return $default ?: $this->get( self::ADS_ID );
	}

	/**
	 * Helper function to retrieve the Merchant Account ID.
	 *
	 * @return int
	 */
	public function get_merchant_id(): int {
		// TODO: Remove overriding with default once ConnectionTest is removed.
		$default = intval( $_GET['merchant_id'] ?? 0 ); // phpcs:ignore WordPress.Security
		return $default ?: $this->get( self::MERCHANT_ID );
	}

	/**
	 * Ensure that a given option key is valid.
	 *
	 * @param string $name The option name.
	 *
	 * @throws InvalidOption When the option key is not valid.
	 */
	protected function validate_option_key( string $name ) {
		if ( ! array_key_exists( $name, self::VALID_OPTIONS ) ) {
			throw InvalidOption::invalid_name( $name );
		}
	}

	/**
	 * Cast to a specific value type.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The option value.
	 *
	 * @return mixed
	 */
	protected function maybe_cast_value( string $name, $value ) {
		if ( isset( self::OPTION_TYPES[ $name ] ) ) {
			/** @var CastableValueInterface $class */
			$class = self::OPTION_TYPES[ $name ];
			$value = $class::cast( $value );
		}

		return $value;
	}

	/**
	 * Convert to a specific value type.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The option value.
	 *
	 * @return mixed
	 * @throws InvalidValue When the value is invalid.
	 */
	protected function maybe_convert_value( string $name, $value ) {
		if ( isset( self::OPTION_TYPES[ $name ] ) ) {
			$class = self::OPTION_TYPES[ $name ];
			$value = new $class( $value );
		}

		return $value;
	}

	/**
	 * Return raw value.
	 *
	 * @param mixed $value Possible object value.
	 *
	 * @return mixed
	 */
	protected function raw_value( $value ) {
		return $value instanceof ValueInterface ? $value->get() : $value;
	}

	/**
	 * Prefix an option name with the plugin prefix.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function prefix_name( string $name ): string {
		return "{$this->get_slug()}_{$name}";
	}
}
