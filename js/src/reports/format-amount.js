/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import formatAmountWithCode from '.~/utils/formatAmountWithCode';

const unavailable = __( 'Unavailable', 'google-listings-and-ads' );

/**
 * Formats given number as currency
 * according to given config, or return `"Unavailable"` if the value is undefined.
 *
 * Entire usage could be simplified once
 * https://github.com/woocommerce/woocommerce-admin/pull/7575 is released and accessible.
 *
 * @param {Object} currencyConfig Currency config object returned by `@woocommerce/currency`.
 * @param {number} value Value to be formatted.
 * @return {string} Formatted currency or "Unavailable".
 */
export function formatCurrencyCell( currencyConfig, value ) {
	if ( value === undefined ) {
		return unavailable;
	}
	return formatAmountWithCode( currencyConfig, value );
}
/**
 * Formats given number according to given config,
 * or return `"Unavailable"` if the value is undefined.
 *
 * We do not use currency code or symbol, but decimal separators, etc.
 *
 * @param {Object} currencyConfig Currency config object returned by `@woocommerce/currency`.
 * @param {number} value Value to be formatted.
 * @return {string} Formatted number or "Unavailable".
 */
export function formatNumericCell( currencyConfig, value ) {
	if ( value === undefined ) {
		return unavailable;
	}
	return numberFormat(
		{
			...currencyConfig,
			precision: 0,
		},
		value
	);
}
