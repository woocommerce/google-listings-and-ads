/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getAvailableLanguagesCurrencies';

/**
 * @typedef {import('~/data/selectors').MCLanguage} MCLanguage
 * @typedef {import('~/data/selectors').MCCurrency} MCCurrency
 */

/**
 * @typedef {Object} AvailableLanguagesCurrencies
 * @property {Array<MCLanguage>|null} languages Available languages from the multilingual integration, or null before data is fetched.
 * @property {Array<MCCurrency>|null} currencies Available currencies from the multilingual integration, or null before data is fetched.
 * @property {boolean} hasFinishedResolution Whether the shared resolver has completed its request.
 */

/**
 * Returns available languages and currencies from the store's multilingual
 * integration (e.g. WPML/WCML). Both values are populated by a single resolver,
 * so this hook fires one request for both.
 *
 * @return {AvailableLanguagesCurrencies} Available languages, currencies, and resolution status.
 */
const useAvailableLanguagesCurrencies = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		// Trigger the shared resolver that populates both languages and currencies.
		selector[ selectorName ]();

		return {
			languages: selector.getAvailableLanguages(),
			currencies: selector.getAvailableStoreCurrencies(),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useAvailableLanguagesCurrencies;
