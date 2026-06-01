/**
 * Internal dependencies
 */
import useAvailableLanguagesCurrencies from './useAvailableLanguagesCurrencies';

/**
 * Returns available languages from the store's multilingual integration.
 * Delegates to useAvailableLanguagesCurrencies so both values share one resolver call.
 *
 * @return {{ languages: Array|null, hasFinishedResolution: boolean }}
 */
const useAvailableLanguages = () => {
	const { languages, hasFinishedResolution } = useAvailableLanguagesCurrencies();
	return { languages, hasFinishedResolution };
};

export default useAvailableLanguages;
