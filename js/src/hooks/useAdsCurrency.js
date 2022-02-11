/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import CurrencyFactory from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import useStoreCurrency from './useStoreCurrency';
import useGoogleAdsAccount from './useGoogleAdsAccount';

/**
 * Get the store's currency config, eventually extended with the Ads' currency code and symbol.
 * This would allow using store's formatting preferences,
 * with an actual currency that's being used by Google Ads Account.
 *
 * We start with a "currency-less" config, as the store's preferences are relatively fast to obtain.
 * So, we could start serving meaningful content to the user, while they wait for Ads details to be fetched.
 *
 * It also exposes the `formatAmount` provided by `CurrencyFactory(config)`.
 *
 * Usage:
 *
 * ```js
 * const { currencyConfig, hasFinishedResolution, formatAmount } = useAdsCurrency()
 * console.log( hasFinishedResolution ) // true
 *
 * console.log( currencyConfig.config )          // { code: 'CAD', symbol: '$', decimalSeparator: '.', thousandSeparator: ',', precision: 2, symbolPosition: 'left_space', priceFormat }
 * console.log( formatAmount( 1234.567 ) )       // '$ 1,234.57'
 * console.log( formatAmount( 1234.567, true ) ) // 'CAD 1,234.57'
 *
 * console.log( currencyConfig.config )          // { code: 'PLN', symbol: 'zł', decimalSeparator: ',', thousandSeparator: '.', precision: 1, … }
 * console.log( formatAmount( 1234.567 ) )       // '1.234,6 zł'
 * console.log( formatAmount( 1234.567, true ) ) // '1.234,6 PLN'
 * ```
 *
 * @see useStoreCurrency
 *
 * @return {{adsCurrencyConfig: Object, hasFinishedResolution: boolean | undefined, formatAmount: Function}} The currency object.
 */
export default function useAdsCurrency() {
	const storeCurrencySetting = useStoreCurrency();
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();

	// Apply store's foramtting data without the Ad's currency and symbol.
	const { currency: code = '', symbol = '' } = googleAdsAccount || {};
	const adsCurrencyConfig = useMemo( () => {
		return {
			...storeCurrencySetting,
			code,
			symbol,
		};
	}, [ storeCurrencySetting, code, symbol ] );

	const formatAmount = useMemo( () => {
		return CurrencyFactory( adsCurrencyConfig ).formatAmount;
	}, [ adsCurrencyConfig ] );

	return {
		adsCurrencyConfig,
		hasFinishedResolution,
		formatAmount,
	};
}

export const useAdsCurrencyConfig = useAdsCurrency;
