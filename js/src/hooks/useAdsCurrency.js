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
 * Get the store's currency object, eventually extended with the Ads' currency and symbol.
 * This would allow using store's formatting preferences,
 * with an actual currency that's being used by Google Ads Account.
 *
 * We start with a "currency-less" config, as the store's preferences are relatively fast to obtain.
 * So, we could start serving meaningful content to the user, while they wait for Ads details to be fetched.
 *
 * Usage:
 *
 * ```js
 * const { currency, isResolving } = useAdsCurrency()
 * console.log( currency.formatAmount( 1234.678 ) ) // Print the number in the store's currency format '1 234,68'.
 * // After some time, once Ads account details are fetched.
 * console.log( currency.hasFinishedResolution ) // true
 * console.log( currency.formatAmount( 1234.678 ) ) // Print the number in the store's currency format with the Ad's currency '1 234,68 zł'.
 * ```
 *
 * @see CurrencyFactory
 *
 * @return {{currency: Object, hasFinishedResolution: boolean | undefined}} The currency object.
 */
const useAdsCurrency = () => {
	const currencySetting = useStoreCurrency();
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();

	return {
		currency: useMemo( () => {
			// Apply store's foramtting data without the Ad's currency.
			if ( googleAdsAccount && googleAdsAccount.currency ) {
				return CurrencyFactory( {
					...currencySetting,
					code: googleAdsAccount.currency,
					symbol: googleAdsAccount.symbol,
				} );
			}
			// Apply store's foramtting data without a specific currency.
			// So we could eagerly render a plain formatted number.
			return CurrencyFactory( {
				...currencySetting,
				code: '',
				symbol: '',
			} );
		}, [ currencySetting, googleAdsAccount ] ),
		hasFinishedResolution,
	};
};

export default useAdsCurrency;
