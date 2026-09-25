/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import CurrencyFactory from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import useStoreCurrency from '~/hooks/useStoreCurrency';

/**
 * Returns a `formatAmount` function that formats a monetary value using a
 * caller-supplied ISO 4217 currency code, combined with the store's formatting
 * preferences (decimal/thousands separators, precision, and price format).
 *
 * This is the multilingual counterpart of `useAdsCurrency`. Where
 * `useAdsCurrency` always formats in the single Ads-account currency,
 * `useMarketCurrency` accepts any currency code so that each market row in the
 * Markets UI can be formatted independently — essential for multilingual stores
 * where different markets operate in different currencies.
 *
 * Usage:
 *
 * ```js
 * const { formatAmount } = useMarketCurrency();
 *
 * // Store configured with EUR formatting (. decimal, , thousands):
 * console.log( formatAmount( 1234.5, 'JPY' ) ); // 'JPY1,234.50'
 * console.log( formatAmount( 50, 'EUR' ) );      // 'EUR50.00'
 * ```
 *
 * Note: the currency symbol is set to the ISO code because per-market symbol
 * data is not available client-side. The store's `priceFormat` determines
 * whether it appears before or after the number.
 *
 * @see useAdsCurrency
 *
 * @return {{ formatAmount: Function }} Object with a `formatAmount( amount, currencyCode )` function.
 */
export default function useMarketCurrency() {
	const storeCurrencySetting = useStoreCurrency();

	/**
	 * Formats a monetary amount using `currencyCode`, inheriting the store's
	 * separator, precision, and price-format preferences.
	 *
	 * @param {number} amount       The numeric value to format.
	 * @param {string} currencyCode ISO 4217 currency code (e.g. 'EUR', 'JPY').
	 * @return {string} Formatted currency string.
	 */
	const formatAmount = useCallback(
		( amount, currencyCode ) => {
			const config = {
				...storeCurrencySetting,
				code: currencyCode ?? storeCurrencySetting.code,
				symbol: currencyCode ?? storeCurrencySetting.symbol,
			};
			return CurrencyFactory( config ).formatAmount( amount );
		},
		[ storeCurrencySetting ]
	);

	return { formatAmount };
}
