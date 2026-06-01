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
 * Returns available languages and currencies from the store's multilingual
 * integration (e.g. WPML/WCML). Both values are populated by a single resolver,
 * so this hook fires one request for both.
 *
 * @return {{ languages: Array|null, currencies: Array|null, hasFinishedResolution: boolean }}
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
