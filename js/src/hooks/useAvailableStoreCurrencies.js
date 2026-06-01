/**
 * Internal dependencies
 */
import useAvailableLanguagesCurrencies from './useAvailableLanguagesCurrencies';

/**
 * Returns available store currencies from the multilingual integration (e.g. WCML).
 * Delegates to useAvailableLanguagesCurrencies so both values share one resolver call.
 *
 * @return {Object} An object containing the available currencies and resolution status.
 */
const useAvailableStoreCurrencies = () => {
	const { currencies, hasFinishedResolution } =
		useAvailableLanguagesCurrencies();
	return { currencies, hasFinishedResolution };
};

export default useAvailableStoreCurrencies;
