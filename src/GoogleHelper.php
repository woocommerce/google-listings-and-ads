<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

/**
 * Trait GoogleHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
trait GoogleHelper {

	/**
	 * Get an array of merchant center supported countries and currencies.
	 *
	 * Note - Other currencies may be supported using currency conversion.
	 *
	 * WooCommerce Countries -> https://github.com/woocommerce/woocommerce/blob/master/i18n/countries.php
	 * Google Supported Countries -> https://support.google.com/merchants/answer/160637?hl=en
	 *
	 * Commented out countries are "listed" as beta countries.
	 *
	 * @param bool $include_beta Whether to include countries supported in Beta by Google.
	 *
	 * @return array
	 */
	protected function get_mc_supported_countries_currencies( bool $include_beta = false ): array {
		$beta_countries = [
			'DZ' => 'DZD', // Algeria
			'AO' => 'AOA', // Angola
			'BD' => 'BDT', // Bangladesh
			'KH' => 'KHR', // Cambodia
			'CM' => 'XAF', // Cameroon
			'CI' => 'XOF', // Cote d'Ivoire
			'DO' => 'DOP', // Dominican Republic
			'SV' => 'USD', // El Salvador
			'ET' => 'ETB', // Ethiopia
			'GH' => 'GHS', // Ghana
			'GT' => 'GTQ', // Guatemala
			'KE' => 'KES', // Kenya
			'MG' => 'MGA', // Madagascar
			'MU' => 'MUR', // Mauritius
			'MA' => 'MAD', // Morocco
			'MZ' => 'MZN', // Mozambique
			'MM' => 'MMK', // Myanmar 'Burma'
			'MP' => 'NPR', // Nepal
			'NI' => 'NIO', // Nicaragua
			'NG' => 'NGN', // Nigeria
			'PK' => 'PKR', // Pakistan
			'PA' => 'PAB', // Panama
			'PR' => 'USD', // Puerto Rico
			'SA' => 'SAR', // Saudi Arabia
			'SN' => 'XOF', // Senegal
			'LK' => 'LKR', // Sri Lanka
			'TZ' => 'TZS', // Tanzania
			'TH' => 'THB', // Thailand
			'TN' => 'TND', // Tunisia
			'VE' => 'VEF', // Venezuela
			'VN' => 'VND', // Vietnam
			'ZM' => 'ZMW', // Zambia
			'ZW' => 'USD', // Zimbabwe
		];

		$supported_countries = [
			'AR' => 'ARS', // Argentina
			'AU' => 'AUD', // Australia
			'AT' => 'EUR', // Austria
			'BH' => 'BHD', // Bahrain
			'BY' => 'BYN', // Belarus
			'BE' => 'EUR', // Belgium
			'BR' => 'BRL', // Brazil
			'CA' => 'CAD', // Canada
			'CL' => 'CLP', // Chile
			'CO' => 'COP', // Colombia
			'CR' => 'CRC', // Costa Rica
			'CZ' => 'CZK', // Czechia
			'DK' => 'DKK', // Denmark
			'EC' => 'USD', // Ecuador
			'EG' => 'EGP', // Egypt
			'FI' => 'EUR', // Finland
			'FR' => 'EUR', // France
			'GE' => 'GEL', // Georgia
			'DE' => 'EUR', // Germany
			'GR' => 'EUR', // Greece
			'HK' => 'HKD', // Hong Kong
			'HU' => 'HUF', // Hungary
			'IN' => 'INR', // India
			'ID' => 'IDR', // Indonesia
			'IE' => 'EUR', // Ireland
			'IL' => 'ILS', // Israel
			'IT' => 'EUR', // Italy
			'JP' => 'JPY', // Japan
			'JO' => 'JOD', // Jordan
			'KZ' => 'KZT', // Kazakhstan
			'KW' => 'KWD', // Kuwait
			'LB' => 'LBP', // Lebanon
			'MY' => 'MYR', // Malaysia
			'MX' => 'MXN', // Mexico
			'NL' => 'EUR', // Netherlands
			'NZ' => 'NZD', // New Zealand
			'NO' => 'NOK', // Norway
			'OM' => 'OMR', // Oman
			'PY' => 'PYG', // Paraguay
			'PE' => 'PEN', // Peru
			'PH' => 'PHP', // Philippines
			'PL' => 'PLN', // Poland
			'PT' => 'EUR', // Portugal
			'RO' => 'RON', // Romania
			'RU' => 'RUB', // Russia
			'SG' => 'SGD', // Singapore
			'SK' => 'EUR', // Slovakia
			'ZA' => 'ZAR', // South Africa
			'KR' => 'KRW', // South Korea
			'ES' => 'EUR', // Spain
			'SE' => 'SEK', // Sweden
			'CH' => 'CHF', // Switzerland
			'TW' => 'TWD', // Taiwan
			'TR' => 'TRY', // Turkey
			'AE' => 'AED', // United Arab Emirates
			'GB' => 'GBP', // United Kingdom
			'US' => 'USD', // United States
			'UY' => 'UYU', // Uruguay
			'UZ' => 'UZS', // Uzbekistan
		];

		return $include_beta ? array_merge( $supported_countries, $beta_countries ) : $supported_countries;
	}

	/**
	 * Get an array of merchant center supported countries.
	 *
	 * WooCommerce Countries -> https://github.com/woocommerce/woocommerce/blob/master/i18n/countries.php
	 * Google Supported Countries -> https://support.google.com/merchants/answer/160637?hl=en
	 *
	 * @param bool $include_beta Whether to include countries supported in Beta by Google.
	 *
	 * @return string[]
	 */
	protected function get_mc_supported_countries( bool $include_beta = false ): array {
		return array_keys( $this->get_mc_supported_countries_currencies( $include_beta ) );
	}
}
