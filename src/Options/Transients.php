<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidOption;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class Transients
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
final class Transients implements TransientsInterface, Service {

	use PluginHelper;

	private const VALID_OPTIONS = [
		self::MC_STATUSES => true,
	];

	/**
	 * Array of transients that we have loaded.
	 *
	 * @var array
	 */
	protected $transients = [];

	/**
	 * Get a transient.
	 *
	 * @param string $name    The transient name.
	 * @param mixed  $default A default value for the transient.
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = null ) {
		$this->validate_transient_key( $name );

		if ( ! array_key_exists( $name, $this->transients ) ) {
			$value = get_transient( $this->prefix_name( $name ) );

			if ( false === $value ) {
				$value = $default;
			}

			$this->transients[ $name ] = $value;
		}

		return $this->transients[ $name ];
	}

	/**
	 * Add or update a transient.
	 *
	 * @param string $name  The transient name.
	 * @param mixed  $value The transient value.
	 * @param int    $expiration Time until expiration in seconds.
	 *
	 * @return bool
	 *
	 * @throws InvalidValue If a boolean $value is provided.
	 */
	public function set( string $name, $value, int $expiration = 0 ): bool {
		if ( is_bool( $value ) ) {
			throw new InvalidValue( 'Transients cannot have boolean values.' );
		}

		$this->validate_transient_key( $name );
		$this->transients[ $name ] = $value;
		return set_transient( $this->prefix_name( $name ), $value, $expiration );
	}

	/**
	 * Delete a transient.
	 *
	 * @param string $name The transient name.
	 *
	 * @return bool
	 */
	public function delete( string $name ): bool {
		$this->validate_transient_key( $name );
		unset( $this->transients[ $name ] );

		return delete_transient( $this->prefix_name( $name ) );
	}

	/**
	 * Ensure that a given transient key is valid.
	 *
	 * @param string $name The transient name.
	 *
	 * @throws InvalidOption When the transient key is not valid.
	 */
	protected function validate_transient_key( string $name ) {
		if ( ! array_key_exists( $name, self::VALID_OPTIONS ) ) {
			throw InvalidOption::invalid_name( $name );
		}
	}

	/**
	 * Prefix a transient name with the plugin prefix.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function prefix_name( string $name ): string {
		return "{$this->get_slug()}_{$name}";
	}
}
