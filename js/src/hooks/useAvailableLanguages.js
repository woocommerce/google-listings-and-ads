/**
 * Internal dependencies
 */
import useAvailableLanguagesCurrencies from './useAvailableLanguagesCurrencies';

/**
 * Returns available languages from the store's multilingual integration.
 * Delegates to useAvailableLanguagesCurrencies so both values share one resolver call.
 *
 * @return {Object} An object containing the available languages and resolution status.
 */
const useAvailableLanguages = () => {
	const { languages, hasFinishedResolution } =
		useAvailableLanguagesCurrencies();
	return { languages, hasFinishedResolution };
};

export default useAvailableLanguages;
