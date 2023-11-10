<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelperAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\ISO3166Awareness;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\Exception\OutOfBoundsException;
use WP_REST_Request as Request;
use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Trait CountryCodeTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
trait CountryCodeTrait {

	use GoogleHelperAwareTrait;
	use ISO3166Awareness;
	use WPErrorTrait;

	/**
	 * Validate that a country is valid.
	 *
	 * @param string $country The alpha2 country code.
	 *
	 * @throws OutOfBoundsException When the country code cannot be found.
	 */
	protected function validate_country_code( string $country ): void {
		$this->iso3166_data_provider->alpha2( $country );
	}

	/**
	 * Validate that a country or a list of countries is valid and supported,
	 * and also validate the data by the built-in validation of WP REST API with parameter’s schema.
	 *
	 * Since this extension's all API endpoints that use this validation function specify both
	 * `validate_callback` and `sanitize_callback`, this makes the built-in schema validation
	 * in WP REST API not to be applied. Therefore, this function calls `rest_validate_request_arg`
	 * first, so that the API endpoints can still benefit from the built-in schema validation.
	 *
	 * @param bool    $check_supported_country  Whether to check the country is supported.
	 * @param mixed   $countries                An individual string or an array of strings.
	 * @param Request $request                  The request to validate.
	 * @param string  $param                    The parameter name, used in error messages.
	 *
	 * @return mixed
	 * @throws Exception            When the country is not supported.
	 * @throws OutOfBoundsException When the country code cannot be found.
	 */
	protected function validate_country_codes( bool $check_supported_country, $countries, $request, $param ) {
		$validation_result = rest_validate_request_arg( $countries, $request, $param );

		if ( true !== $validation_result ) {
			return $validation_result;
		}

		try {
			// This is used for individual strings and an array of strings.
			$countries = (array) $countries;

			foreach ( $countries as $country ) {
				$this->validate_country_code( $country );
				if ( $check_supported_country ) {
					$country_supported = $this->google_helper->is_country_supported( $country );
					if ( ! $country_supported ) {
						throw new Exception( __( 'Country is not supported', 'google-listings-and-ads' ) );
					}
				}
			}
			return true;
		} catch ( Throwable $e ) {
			return $this->error_from_exception(
				$e,
				'gla_invalid_country',
				[
					'status'  => 400,
					'country' => $countries,
				]
			);
		}
	}

	/**
	 * Get the callback to sanitize the country code.
	 *
	 * Necessary because strtoupper() will trigger warnings when extra parameters are passed to it.
	 *
	 * @return callable
	 */
	protected function get_country_code_sanitize_callback(): callable {
		return function ( $value ) {
			return is_array( $value )
				? array_map( 'strtoupper', $value )
				: strtoupper( $value );
		};
	}

	/**
	 * Get a callable function for validating that a provided country code is recognized
	 * and fulfilled the given parameter's schema.
	 *
	 * @return callable
	 */
	protected function get_country_code_validate_callback(): callable {
		return function ( ...$args ) {
			return $this->validate_country_codes( false, ...$args );
		};
	}

	/**
	 * Get a callable function for validating that a provided country code is recognized, supported,
	 * and fulfilled the given parameter's schema..
	 *
	 * @return callable
	 */
	protected function get_supported_country_code_validate_callback(): callable {
		return function ( ...$args ) {
			return $this->validate_country_codes( true, ...$args );
		};
	}
}
