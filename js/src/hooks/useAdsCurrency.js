/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

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
 * Usage:
 *
 * ```js
 * const { currencyConfig, hasFinishedResolution } = useAdsCurrency()
 * console.log( currencyConfig.config ) // { code: 'CAD', symbol: '$', precision: 2, symbolPosition: 'left_space', decimalSeparator: '.', thousandSeparator: ',', priceFormat }
 * console.log( hasFinishedResolution ) // true
 * console.log( currencyConfig.config ) // { code: 'PLN', symbol: 'zł', … }
 * ```
 *
 * Once https://github.com/woocommerce/woocommerce-admin/pull/7575 will be available throught Dependency Extraction Webpack Plugin,
 * we can consider exposing currency object given by `CurrencyFactory`, to expose `formatAmout` already customized for the currency.
 *
 * @see useStoreCurrency
 *
 * @return {{adsCurrencyConfig: Object, hasFinishedResolution: boolean | undefined}} The currency object.
 */
export const useAdsCurrencyConfig = () => {
	const storeCurrencySetting = useStoreCurrency();
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();

	const adsCurrencyConfig = useMemo( () => {
		// Apply store's foramtting data without the Ad's currency.
		if ( googleAdsAccount && googleAdsAccount.currency ) {
			return {
				...storeCurrencySetting,
				code: googleAdsAccount.currency,
				symbol: googleAdsAccount.symbol,
			};
		}
		// Apply store's foramtting data without a specific currency.
		// So we could eagerly render a plain formatted number.
		return {
			...storeCurrencySetting,
			code: '',
			symbol: '',
		};
	}, [ storeCurrencySetting, googleAdsAccount ] );

	return {
		adsCurrencyConfig,
		hasFinishedResolution,
	};
};
