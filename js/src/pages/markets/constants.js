/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.

export const WC_SHIPPING_SETTINGS_URL =
	getSetting( 'adminUrl' ) + 'admin.php?page=wc-settings&tab=shipping';

export const GOOGLE_MERCHANT_CENTER_URL = 'https://merchants.google.com/';

/**
 * Identifier of the always-present "primary" market.
 *
 * Several actions (e.g. delete) are not applicable to the primary market and
 * use this constant to short-circuit their eligibility.
 */
export const PRIMARY_MARKET_ID = 'primary';

// Languages supported by Google Merchant Center as contentLanguage values.
// Source: https://support.google.com/merchants/answer/160637
export const MC_SUPPORTED_LANGUAGES = new Set( [
	'af',
	'ar',
	'az',
	'be',
	'bg',
	'bn',
	'ca',
	'cs',
	'cy',
	'da',
	'de',
	'el',
	'en',
	'es',
	'et',
	'eu',
	'fa',
	'fi',
	'fr',
	'gl',
	'gu',
	'he',
	'hi',
	'hr',
	'hu',
	'hy',
	'id',
	'is',
	'it',
	'ja',
	'ka',
	'km',
	'ko',
	'ky',
	'lt',
	'lv',
	'mk',
	'ml',
	'mn',
	'mr',
	'ms',
	'my',
	'nl',
	'no',
	'pa',
	'pl',
	'pt',
	'ro',
	'ru',
	'sk',
	'sl',
	'sq',
	'sr',
	'sv',
	'sw',
	'ta',
	'te',
	'th',
	'tl',
	'tr',
	'uk',
	'ur',
	'uz',
	'vi',
	'zh',
] );
