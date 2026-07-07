/**
 * Internal dependencies
 */
import useAvailableLanguagesCurrencies from './useAvailableLanguagesCurrencies';

/**
 * @typedef {import('~/data/selectors').MCLanguage} MCLanguage
 */

/**
 * @typedef {Object} AvailableStoreLanguages
 * @property {Array<MCLanguage>|null} languages Available languages from the multilingual integration, or null before data is fetched.
 * @property {boolean} hasFinishedResolution Whether the shared resolver has completed its request.
 */

/**
 * Returns available languages from the store's multilingual integration (e.g. WPML).
 * Delegates to useAvailableLanguagesCurrencies so both values share one resolver call.
 *
 * @return {AvailableStoreLanguages} Available languages and resolution status.
 */
const useAvailableStoreLanguages = () => {
	const { languages, hasFinishedResolution } =
		useAvailableLanguagesCurrencies();

	return { languages, hasFinishedResolution };
};

export default useAvailableStoreLanguages;
