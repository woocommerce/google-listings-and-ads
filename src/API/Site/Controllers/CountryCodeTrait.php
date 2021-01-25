<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\Exception\OutOfBoundsException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Trait CountryCodeTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
trait CountryCodeTrait {

	use WPErrorTrait;

	/**
	 * @var ISO3166DataProvider
	 */
	protected $iso;

	/**
	 * Validate that a country is valid.
	 *
	 * @param string $country The alpha2 country code.
	 *
	 * @throws OutOfBoundsException When the country code cannot be found.
	 */
	protected function validate_country_code( string $country ): void {
		$this->iso->alpha2( $country );
	}

	/**
	 * Get the callback to sanitize the country code.
	 *
	 * Necessary because strtoupper() will trigger warnings when extra parameters are passed to it.
	 *
	 * @return callable
	 */
	protected function get_country_code_sanitize_callback(): callable {
		return function( $value ) {
			return is_array( $value )
				? array_map( 'strtoupper', $value )
				: strtoupper( $value );
		};
	}

	/**
	 * Get a callable function for validating that a provided country code is recognized.
	 *
	 * @return callable
	 */
	protected function get_country_code_validate_callback(): callable {
		return function( $value ) {
			try {
				// This is used for individual strings and an array of strings.
				$value = (array) $value;
				foreach ( $value as $item ) {
					$this->validate_country_code( $item );
				}

				return true;
			} catch ( Throwable $e ) {
				return $this->error_from_exception(
					$e,
					'gla_invalid_country',
					[
						'status'  => 400,
						'country' => $item,
					]
				);
			}
		};
	}
}
