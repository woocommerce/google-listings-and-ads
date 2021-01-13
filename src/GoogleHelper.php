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
	 * @return array
	 */
	protected function get_mc_supported_countries() {
		// phpcs:disable Squiz.PHP.CommentedOutCode.Found
		return [
			// 'DZ' => 'DZD', // Algeria
			// 'AO' => 'AOA', // Angola
			'AR' => 'ARS', // Argentina
			'AU' => 'AUD', // Australia
			'AT' => 'EUR', // Austria
			'BH' => 'BHD', // Bahrain
			// 'BD' => 'BDT', // Bangladesh
			'BY' => 'BYN', // Belarus
			'BE' => 'EUR', // Belgium
			'BR' => 'BRL', // Brazil
			// 'KH' => 'KHR', // Cambodia
			// 'CM' => 'XAF', // Cameroon
			'CA' => 'CAD', // Canada
			'CL' => 'CLP', // Chile
			'CO' => 'COP', // Colombia
			'CR' => 'CRC', // Costa Rica
			// 'CI' => 'XOF', // Cote d'Ivoire
			'CZ' => 'CZK', // Czechia
			'DK' => 'DKK', // Denmark
			// 'DO' => 'DOP', // Dominican Republic
			'EC' => 'USD', // Ecuador
			'EG' => 'EGP', // Egypt
			// 'SV' => 'USD', // El Salvador
			// 'ET' => 'ETB', // Ethiopia
			'FI' => 'EUR', // Finland
			'FR' => 'EUR', // France
			'GE' => 'GEL', // Georgia
			'DE' => 'EUR', // Germany
			// 'GH' => 'GHS', // Ghana
			'GR' => 'EUR', // Greece
			// 'GT' => 'GTQ', // Guatemala
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
			// 'KE' => 'KES', // Kenya
			'KW' => 'KWD', // Kuwait
			'LB' => 'LBP', // Lebanon
			// 'MG' => 'MGA', // Madagascar
			'MY' => 'MYR', // Malaysia
			// 'MU' => 'MUR', // Mauritius
			'MX' => 'MXN', // Mexico
			// 'MA' => 'MAD', // Morocco
			// 'MZ' => 'MZN', // Mozambique
			// 'MM' => 'MMK', // Myanmar 'Burma'
			// 'MP' => 'NPR', // Nepal
			'NL' => 'EUR', // Netherlands
			'NZ' => 'NZD', // New Zealand
			// 'NI' => 'NIO', // Nicaragua
			// 'NG' => 'NGN', // Nigeria
			'NO' => 'NOK', // Norway
			'OM' => 'OMR', // Oman
			// 'PK' => 'PKR', // Pakistan
			// 'PA' => 'PAB', // Panama
			'PY' => 'PYG', // Paraguay
			'PE' => 'PEN', // Peru
			'PH' => 'PHP', // Philippines
			'PL' => 'PLN', // Poland
			'PT' => 'EUR', // Portugal
			// 'PR' => 'USD', // Puerto Rico
			'RO' => 'RON', // Romania
			'RU' => 'RUB', // Russia
			// 'SA' => 'SAR', // Saudi Arabia
			// 'SN' => 'XOF', // Senegal
			'SG' => 'SGD', // Singapore
			'SK' => 'EUR', // Slovakia
			'ZA' => 'ZAR', // South Africa
			'KR' => 'KRW', // South Korea
			'ES' => 'EUR', // Spain
			// 'LK' => 'LKR', // Sri Lanka
			'SE' => 'SEK', // Sweden
			'CH' => 'CHF', // Switzerland
			'TW' => 'TWD', // Taiwan
			// 'TZ' => 'TZS', // Tanzania
			// 'TH' => 'THB', // Thailand
			// 'TN' => 'TND', // Tunisia
			'TR' => 'TRY', // Turkey
			// 'UG' => 'UGX', // Uganda
			// 'UA' => 'UAH', // Ukraine
			'AE' => 'AED', // United Arab Emirates
			'GB' => 'GBP', // United Kingdom
			'US' => 'USD', // United States
			'UY' => 'UYU', // Uruguay
			'UZ' => 'UZS', // Uzbekistan
			// 'VE' => 'VEF', // Venezuela
			// 'VN' => 'VND', // Vietnam
			// 'ZM' => 'ZMW', // Zambia
			// 'ZW' => 'USD', // Zimbabwe
		];
		// phpcs:enable
	}
}
