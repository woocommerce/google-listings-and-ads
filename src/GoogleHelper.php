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
	 * Get an array of Google Merchant Center supported countries and currencies.
	 *
	 * Note - Other currencies may be supported using currency conversion.
	 *
	 * WooCommerce Countries -> https://github.com/woocommerce/woocommerce/blob/master/i18n/countries.php
	 * Google Supported Countries -> https://support.google.com/merchants/answer/160637?hl=en
	 *
	 * @param bool $include_beta Whether to include countries supported in Beta by Google.
	 *
	 * @return array
	 */
	protected function get_mc_supported_countries_currencies( bool $include_beta = true ): array {
		$beta_countries = [
			'DZ' => 'DZD', // Algeria
			'AO' => 'AOA', // Angola
			'BD' => 'BDT', // Bangladesh
			'KH' => 'KHR', // Cambodia
			'CM' => 'XAF', // Cameroon
			'CR' => 'CRC', // Costa Rica
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
			'NP' => 'NPR', // Nepal
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
			'UG' => 'UGX', // Uganda
			'UA' => 'UAH', // Ukraine
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

		$supported = $include_beta ? array_merge( $supported_countries, $beta_countries ) : $supported_countries;

		// Currency conversion is unavailable in South Korea: https://support.google.com/merchants/answer/7055540
		if ( 'KRW' === get_woocommerce_currency() ) {
			$supported['KR'] = 'KRW'; // South Korea
		}

		return $supported;
	}

	/**
	 * Get an array of Google Merchant Center supported countries.
	 *
	 * WooCommerce Countries -> https://github.com/woocommerce/woocommerce/blob/master/i18n/countries.php
	 * Google Supported Countries -> https://support.google.com/merchants/answer/160637?hl=en
	 *
	 * @param bool $include_beta Whether to include countries supported in Beta by Google.
	 *
	 * @return string[]
	 */
	protected function get_mc_supported_countries( bool $include_beta = true ): array {
		return array_keys( $this->get_mc_supported_countries_currencies( $include_beta ) );
	}

	/**
	 * Get an array of Google Merchant Center supported countries and currencies for promotions.
	 *
	 * @return array
	 */
	protected function get_mc_promotion_supported_countries_currencies(): array {
		return [
			'US' => 'USD', // United States
		];
	}

	/**
	 * Get an array of Google Merchant Center supported countries for promotions.
	 *
	 * @return string[]
	 */
	protected function get_mc_promotion_supported_countries(): array {
		return array_keys( $this->get_mc_promotion_supported_countries_currencies() );
	}

	/**
	 * Get an array of Google Merchant Center supported languages (ISO 639-1).
	 *
	 * WooCommerce Languages -> https://translate.wordpress.org/projects/wp-plugins/woocommerce/
	 * Google Supported Languages -> https://support.google.com/merchants/answer/160637?hl=en
	 * ISO 639-1 -> https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 *
	 * @return array
	 */
	protected function get_mc_supported_languages(): array {
		// Repeated values removed:
		// 'pt', // Brazilian Portuguese
		// 'zh', // Simplified Chinese*

		return [
			'ar' => 'ar', // Arabic
			'cs' => 'cs', // Czech
			'da' => 'da', // Danish
			'nl' => 'nl', // Dutch
			'en' => 'en', // English
			'fi' => 'fi', // Finnish
			'fr' => 'fr', // French
			'de' => 'de', // German
			'he' => 'he', // Hebrew
			'hu' => 'hu', // Hungarian
			'id' => 'id', // Indonesian
			'it' => 'it', // Italian
			'ja' => 'ja', // Japanese
			'ko' => 'ko', // Korean
			'el' => 'el', // Modern Greek
			'nb' => 'nb', // Norwegian (Norsk Bokmål)
			'nn' => 'nn', // Norwegian (Norsk Nynorsk)
			'no' => 'no', // Norwegian
			'pl' => 'pl', // Polish
			'pt' => 'pt', // Portuguese
			'ro' => 'ro', // Romanian
			'ru' => 'ru', // Russian
			'sk' => 'sk', // Slovak
			'es' => 'es', // Spanish
			'sv' => 'sv', // Swedish
			'th' => 'th', // Thai
			'zh' => 'zh', // Traditional Chinese
			'tr' => 'tr', // Turkish
			'uk' => 'uk', // Ukrainian
			'vi' => 'vi', // Vietnamese
		];
	}

	/**
	 * Get whether the country is supported by the Merchant Center.
	 *
	 * @param  string $country Country code.
	 *
	 * @return bool True if the country is in the list of MC-supported countries.
	 *
	 * @since 1.9.0
	 */
	protected function is_country_supported( string $country ): bool {
		return array_key_exists(
			strtoupper( $country ),
			$this->get_mc_supported_countries_currencies()
		);
	}
}
