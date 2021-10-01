/**
 * External dependencies
 */
import { sprintf } from '@wordpress/i18n';
import { numberFormat } from '@woocommerce/number';

/**
 * Formats money value with currency code.
 *
 * This is almost a clone of `@woocommerce/currency`'s `formatAmount`,
 * but a one that uses currency code instead of symbol to avoid ambiguity.
 * Preferably, we would do that by just setting a parameter on build in `formatAmount` without a need to recreating it.
 *
 *
 * @param {Object} currency Currency config object returned by `@woocommerce/currency`.
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
