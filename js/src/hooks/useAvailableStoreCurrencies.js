/**
 * Internal dependencies
 */
import useAvailableLanguagesCurrencies from './useAvailableLanguagesCurrencies';

/**
 * @typedef {import('~/data/selectors').MCCurrency} MCCurrency
 */

/**
 * @typedef {Object} AvailableStoreCurrencies
 * @property {Array<MCCurrency>|null} currencies Available currencies from the multilingual integration, or null before data is fetched.
 * @property {boolean} hasFinishedResolution Whether the shared resolver has completed its request.
 */

/**
 * Returns available store currencies from the multilingual integration (e.g. WCML).
 * Delegates to useAvailableLanguagesCurrencies so both values share one resolver call.
 *
 * @return {AvailableStoreCurrencies} Available currencies and resolution status.
 */
const useAvailableStoreCurrencies = () => {
	const { currencies, hasFinishedResolution } =
		useAvailableLanguagesCurrencies();

	return { currencies, hasFinishedResolution };
};

export default useAvailableStoreCurrencies;
