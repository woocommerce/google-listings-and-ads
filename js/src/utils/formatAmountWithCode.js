/**
 * External dependencies
 */
import { sprintf } from '@wordpress/i18n';
import { numberFormat } from '@woocommerce/number';

// `CurencyConfig` type definition could be imported after
// https://github.com/woocommerce/woocommerce-admin/pull/7848
/**
 * Currency config format from the `@woocommerce/currency`.
 * Please note, the properties of this object are not defined in this repo.
 *
 * @typedef {Object} CurencyConfig
 * @property {string} code Currency ISO code.
 * @property {string} symbol Symbol, can be multi-character.
 * @property {string} symbolPosition Where the symbol should be relative to the amount. One of `'left' | 'right' | 'left_space | 'right_space'`.
 * @property {string} thousandSeparator Character used to separate thousands groups.
 * @property {string} decimalSeparator Decimal separator.
 * @property {number} precision Decimal precision.
 */

/**
 * Formats money value with currency code.
 *
 * This is almost a clone of `@woocommerce/currency`'s `formatAmount`,
 * but a one that uses currency code instead of symbol to avoid ambiguity.
 * Preferably, we would do that by just setting a parameter on build in `formatAmount` without a need to recreating it.
 *
 * To be removed once https://github.com/woocommerce/woocommerce-admin/pull/7575 is released and accessible.
 *
 *
 * @param {CurencyConfig} currency Currency config object.
 * @param {number} number Amount to be formatted.
 * @return {string} Amount foramtted according to given config, with the currency symbol.
 */
export default function formatAmountWithCode( currency, number ) {
	const formattedNumber = numberFormat( currency, number );

	if ( formattedNumber === '' ) {
		return formattedNumber;
	}

	const priceFormat = currency.priceFormat,
		code = currency.code;

	return sprintf( priceFormat, code, formattedNumber );
}
