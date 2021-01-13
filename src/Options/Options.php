<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidOption;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class Options
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
final class Options implements OptionsInterface, Service {

	use PluginHelper;

	private const VALID_OPTIONS = [
		self::MC_SETUP_COMPLETE => true,
		self::MERCHANT_CENTER   => true,
		self::MERCHANT_ID       => true,
		self::SHIPPING_RATES    => true,
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
		$name = $this->prefix_name( $name );
		if ( ! array_key_exists( $name, $this->options ) ) {
			$this->options[ $name ] = get_option( $name, $default );
		}

		return $this->options[ $name ];
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
		$name                   = $this->prefix_name( $name );
		$this->options[ $name ] = $value;

		return update_option( $name, $value );
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
		$name = $this->prefix_name( $name );
		unset( $this->options[ $name ] );

		return delete_option( $name );
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
